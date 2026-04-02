<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function locations(): JsonResponse
    {
        $locations = AttendanceLocation::active()->orderBy('name')->get([
            'id', 'name', 'type', 'latitude', 'longitude', 'radius_meters',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi absensi.',
            'data'    => $locations,
        ]);
    }

    public function checkin(Request $request): JsonResponse
    {
        $request->validate([
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'job_order_id' => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
            'selfie'       => 'nullable|image|max:5120',
        ]);

        $user = $request->user();

        // Prevent duplicate checkin without checkout
        $openCheckin = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('type', 'checkin')
            ->whereNotExists(function ($q) use ($user) {
                $q->from('attendances as co')
                    ->whereColumn('co.user_id', 'attendances.user_id')
                    ->whereDate('co.created_at', today())
                    ->where('co.type', 'checkout');
            })
            ->latest()
            ->first();

        if ($openCheckin) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah checkin hari ini. Lakukan checkout terlebih dahulu.',
                'data'    => null,
            ], 422);
        }

        [$locationId, $verifiedAt] = $this->resolveLocation(
            $request->latitude,
            $request->longitude
        );

        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('attendance/selfies', 'public');
        }

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'location_id'  => $locationId,
            'job_order_id' => $request->job_order_id,
            'type'         => 'checkin',
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
            'selfie_path'  => $selfiePath,
            'verified_at'  => $verifiedAt,
            'notes'        => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => $verifiedAt ? 'Checkin berhasil (terverifikasi).' : 'Checkin berhasil (di luar zona, menunggu verifikasi).',
            'data'    => $this->formatAttendance($attendance->load('location')),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes'     => 'nullable|string|max:500',
            'selfie'    => 'nullable|image|max:5120',
        ]);

        $user = $request->user();

        $openCheckin = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('type', 'checkin')
            ->latest()
            ->first();

        if (! $openCheckin) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada checkin hari ini.',
                'data'    => null,
            ], 422);
        }

        [$locationId, $verifiedAt] = $this->resolveLocation(
            $request->latitude,
            $request->longitude
        );

        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('attendance/selfies', 'public');
        }

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'location_id' => $locationId,
            'type'        => 'checkout',
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'selfie_path' => $selfiePath,
            'verified_at' => $verifiedAt,
            'notes'       => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checkout berhasil.',
            'data'    => $this->formatAttendance($attendance->load('location')),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $records = Attendance::with('location')
            ->where('user_id', $request->user()->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $mon)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($a) => $this->formatAttendance($a));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat absensi.',
            'data'    => $records,
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $records = Attendance::with('location')
            ->where('user_id', $request->user()->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at')
            ->get()
            ->map(fn($a) => $this->formatAttendance($a));

        return response()->json([
            'success' => true,
            'message' => 'Absensi hari ini.',
            'data'    => $records,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'records'              => 'required|array|min:1|max:100',
            'records.*.type'       => 'required|in:checkin,checkout',
            'records.*.latitude'   => 'required|numeric|between:-90,90',
            'records.*.longitude'  => 'required|numeric|between:-180,180',
            'records.*.created_at' => 'required|date',
            'records.*.job_order_id' => 'nullable|string|max:100',
            'records.*.notes'      => 'nullable|string|max:500',
        ]);

        $userId  = $request->user()->id;
        $synced  = 0;
        $skipped = 0;

        foreach ($request->records as $record) {
            $exists = Attendance::where('user_id', $userId)
                ->where('created_at', $record['created_at'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            [$locationId, $verifiedAt] = $this->resolveLocation(
                $record['latitude'],
                $record['longitude']
            );

            Attendance::create([
                'user_id'         => $userId,
                'location_id'     => $locationId,
                'job_order_id'    => $record['job_order_id'] ?? null,
                'type'            => $record['type'],
                'latitude'        => $record['latitude'],
                'longitude'       => $record['longitude'],
                'verified_at'     => $verifiedAt,
                'notes'           => $record['notes'] ?? null,
                'is_offline_sync' => true,
                'created_at'      => $record['created_at'],
                'updated_at'      => $record['created_at'],
            ]);

            $synced++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Sync selesai.',
            'data'    => [
                'synced'  => $synced,
                'skipped' => $skipped,
            ],
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Haversine formula — returns distance in meters between two coordinates.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // metres

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Find the nearest active location and check if within radius.
     * Returns [location_id|null, verified_at|null].
     */
    private function resolveLocation(float $lat, float $lng): array
    {
        $locations = AttendanceLocation::active()->get();

        foreach ($locations as $loc) {
            $distance = $this->haversineDistance($lat, $lng, $loc->latitude, $loc->longitude);
            if ($distance <= $loc->radius_meters) {
                return [$loc->id, now()];
            }
        }

        return [null, null];
    }

    private function formatAttendance(Attendance $a): array
    {
        return [
            'id'              => $a->id,
            'type'            => $a->type,
            'location'        => $a->location ? ['id' => $a->location->id, 'name' => $a->location->name] : null,
            'job_order_id'    => $a->job_order_id,
            'latitude'        => $a->latitude,
            'longitude'       => $a->longitude,
            'is_verified'     => ! is_null($a->verified_at),
            'is_offline_sync' => $a->is_offline_sync,
            'notes'           => $a->notes,
            'timestamp'       => $a->created_at?->toIso8601String(),
        ];
    }
}
