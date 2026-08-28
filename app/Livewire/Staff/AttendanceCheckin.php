<?php

namespace App\Livewire\Staff;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class AttendanceCheckin extends Component
{
    use WithFileUploads;

    public $latitude = null;
    public $longitude = null;
    public $locationStatus = 'Menunggu izin lokasi GPS browser...';
    public $nearestLocationName = null;
    public $nearestDistance = null;
    public $isWithinRadius = false;
    public $notes = '';
    public $selfieData = null; // base64 string from webcam
    public $selfieFile = null; // file upload fallback

    public $todayCheckin = null;
    public $todayCheckout = null;
    public $recentHistory = [];
    public $activeLocations = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $userId = Auth::id();
        $today = Carbon::today();

        $this->todayCheckin = Attendance::with('location')
            ->where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->where('type', 'checkin')
            ->latest()
            ->first();

        $this->todayCheckout = Attendance::with('location')
            ->where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->where('type', 'checkout')
            ->latest()
            ->first();

        $this->recentHistory = Attendance::with('location')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $this->activeLocations = AttendanceLocation::active()->get();
    }

    public function setCoordinates($lat, $lng)
    {
        $this->latitude = (float) $lat;
        $this->longitude = (float) $lng;

        // Hitung jarak ke lokasi terdekat
        $locations = AttendanceLocation::active()->get();
        $nearest = null;
        $minDistance = INF;

        foreach ($locations as $loc) {
            $dist = $this->haversineDistance($this->latitude, $this->longitude, (float) $loc->latitude, (float) $loc->longitude);
            if ($dist < $minDistance) {
                $minDistance = $dist;
                $nearest = $loc;
            }
        }

        if ($nearest) {
            $this->nearestLocationName = $nearest->name;
            $this->nearestDistance = round($minDistance);
            $this->isWithinRadius = $minDistance <= $nearest->radius_meters;

            if ($this->isWithinRadius) {
                $this->locationStatus = "✅ Berada di area: {$nearest->name} (" . round($minDistance) . "m dari titik pusat)";
            } else {
                $this->locationStatus = "⚠️ Di luar radius kantor ({$nearest->name}, jarak " . round($minDistance) . "m, maks {$nearest->radius_meters}m)";
            }
        } else {
            $this->locationStatus = "📍 Koordinat terdeteksi: {$lat}, {$lng}";
        }
    }

    public function submitCheckin()
    {
        if ($this->todayCheckin && !$this->todayCheckout) {
            session()->flash('error', 'Anda sudah melakukan Check-In hari ini.');
            return;
        }

        $this->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes'     => 'nullable|string|max:500',
        ], [
            'latitude.required' => 'Lokasi GPS belum terdeteksi. Izinkan akses lokasi browser Anda.',
        ]);

        $user = Auth::user();
        [$locationId, $verifiedAt] = $this->resolveLocation($this->latitude, $this->longitude);

        $selfiePath = $this->saveSelfiePhoto($user->id);

        $att = Attendance::create([
            'user_id'     => $user->id,
            'location_id' => $locationId,
            'type'        => 'checkin',
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'selfie_path' => $selfiePath,
            'verified_at' => $verifiedAt,
            'notes'       => $this->notes ?: ($this->isWithinRadius ? 'Check-in Web Portal' : 'Check-in Web Portal (Di luar zona)'),
        ]);

        ActivityLog::record(
            'Attendance',
            'CHECKIN',
            $user->name,
            "Check-in Web Portal: " . ($locationId ? 'Terverifikasi di ' . $this->nearestLocationName : 'Di luar radius') . " ({$this->latitude}, {$this->longitude})"
        );

        $this->reset(['notes', 'selfieData', 'selfieFile']);
        $this->loadData();

        session()->flash('success', '🎉 Check-In Masuk berhasil dicatat!' . ($verifiedAt ? ' (Lokasi Terverifikasi)' : ' (Menunggu verifikasi admin)'));
    }

    public function submitCheckout()
    {
        if (!$this->todayCheckin) {
            session()->flash('error', 'Anda belum melakukan Check-In hari ini.');
            return;
        }

        if ($this->todayCheckout) {
            session()->flash('error', 'Anda sudah melakukan Check-Out hari ini.');
            return;
        }

        $this->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes'     => 'nullable|string|max:500',
        ], [
            'latitude.required' => 'Lokasi GPS belum terdeteksi. Izinkan akses lokasi browser Anda.',
        ]);

        $user = Auth::user();
        [$locationId, $verifiedAt] = $this->resolveLocation($this->latitude, $this->longitude);

        $selfiePath = $this->saveSelfiePhoto($user->id);

        $att = Attendance::create([
            'user_id'     => $user->id,
            'location_id' => $locationId,
            'type'        => 'checkout',
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'selfie_path' => $selfiePath,
            'verified_at' => $verifiedAt,
            'notes'       => $this->notes ?: ($this->isWithinRadius ? 'Check-out Web Portal' : 'Check-out Web Portal (Di luar zona)'),
        ]);

        ActivityLog::record(
            'Attendance',
            'CHECKOUT',
            $user->name,
            "Check-out Web Portal: " . ($locationId ? 'Terverifikasi di ' . $this->nearestLocationName : 'Di luar radius') . " ({$this->latitude}, {$this->longitude})"
        );

        $this->reset(['notes', 'selfieData', 'selfieFile']);
        $this->loadData();

        session()->flash('success', '🏁 Check-Out Pulang berhasil dicatat! Terima kasih atas dedikasi Anda hari ini.');
    }

    protected function saveSelfiePhoto(int $userId): ?string
    {
        // 1. Dari base64 webcam capture
        if ($this->selfieData && str_starts_with($this->selfieData, 'data:image')) {
            try {
                $imageParts = explode(';base64,', $this->selfieData);
                if (isset($imageParts[1])) {
                    $imageData = base64_decode($imageParts[1]);
                    $fileName = 'attendance/selfies/web_' . $userId . '_' . time() . '_' . Str::random(6) . '.jpg';
                    Storage::disk('public')->put($fileName, $imageData);
                    return $fileName;
                }
            } catch (\Throwable $e) {
                // Fallback silently
            }
        }

        // 2. Dari file upload
        if ($this->selfieFile) {
            return $this->selfieFile->store('attendance/selfies', 'public');
        }

        return null;
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function resolveLocation(float $lat, float $lng): array
    {
        $locations = AttendanceLocation::active()->get();

        foreach ($locations as $loc) {
            $distance = $this->haversineDistance($lat, $lng, (float) $loc->latitude, (float) $loc->longitude);
            if ($distance <= $loc->radius_meters) {
                return [$loc->id, now()];
            }
        }

        return [null, null];
    }

    public function render()
    {
        return view('livewire.staff.attendance-checkin')
            ->layout('layouts.admin', ['title' => 'Presensi / Absensi Staf M2B']);
    }
}
