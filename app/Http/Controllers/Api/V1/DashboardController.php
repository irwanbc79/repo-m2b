<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $user  = $request->user();
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);
        $periodStart  = Carbon::create($year, $mon, 1)->startOfMonth();
        $periodEnd    = $periodStart->copy()->endOfMonth();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary.',
            'data'    => [
                'user'             => $this->buildUserProfile($user),
                'today'            => $this->buildTodaySummary($user),
                'monthly_summary'  => $this->buildMonthlySummary($user, $periodStart, $periodEnd),
                'leave_balance'    => $this->buildLeaveBalance($user, (int) $year),
                'recent_activity'  => $this->buildRecentActivity($user),
            ],
        ]);
    }

    // ─── Builders ───────────────────────────────────────────────────────────────

    private function buildUserProfile($user): array
    {
        return [
            'name'     => $user->name,
            'position' => $user->getPrimaryRole() ?? 'Staff',
            'avatar_url' => null, // future: Backblaze avatar path
        ];
    }

    private function buildTodaySummary($user): array
    {
        $todayRecords = Attendance::with('location')
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at')
            ->get();

        $checkin  = $todayRecords->firstWhere('type', 'checkin');
        $checkout = $todayRecords->firstWhere('type', 'checkout');

        if ($checkin && $checkout) {
            $status = 'checked_out';
        } elseif ($checkin) {
            $status = 'checked_in';
        } else {
            $status = 'absent';
        }

        return [
            'checkin_time'  => $checkin?->created_at?->format('H:i'),
            'checkout_time' => $checkout?->created_at?->format('H:i'),
            'status'        => $status,
            'location_name' => $checkin?->location?->name,
        ];
    }

    private function buildMonthlySummary($user, Carbon $start, Carbon $end): array
    {
        $workStart = '08:00';
        $lateThreshold = '08:15';

        // Total working days in month (Mon-Fri only)
        $totalDays = 0;
        $day = $start->copy();
        while ($day->lte($end)) {
            if ($day->isWeekday()) {
                $totalDays++;
            }
            $day->addDay();
        }

        // Days user checked in
        $checkinDays = Attendance::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('type', 'checkin')
            ->selectRaw('DATE(created_at) as date, MIN(created_at) as first_checkin')
            ->groupBy('date')
            ->get();

        $present = $checkinDays->count();
        $late    = $checkinDays->filter(fn($r) => Carbon::parse($r->first_checkin)->format('H:i') > $lateThreshold)->count();
        $onTime  = $present - $late;

        // Approved leaves in this month
        $leaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            })
            ->count();

        $absent = max(0, $totalDays - $present - $leaves);

        return [
            'month'      => $start->format('F Y'),
            'total_days' => $totalDays,
            'present'    => $present,
            'on_time'    => $onTime,
            'late'       => $late,
            'leaves'     => $leaves,
            'absent'     => $absent,
        ];
    }

    private function buildLeaveBalance($user, int $year): array
    {
        $annual = 12; // standard annual leave

        $used = LeaveRequest::where('user_id', $user->id)
            ->where('type', 'cuti')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->get()
            ->sum(fn($l) => $l->start_date->diffInDays($l->end_date) + 1);

        return [
            'annual'    => $annual,
            'used'      => (int) $used,
            'remaining' => max(0, $annual - (int) $used),
        ];
    }

    private function buildRecentActivity($user): array
    {
        $activities = collect();

        // Last 5 attendances
        Attendance::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->each(function ($a) use (&$activities) {
                $activities->push([
                    'type'        => $a->type,
                    'description' => $a->type === 'checkin' ? 'Checkin absensi' : 'Checkout absensi',
                    'timestamp'   => $a->created_at?->toIso8601String(),
                ]);
            });

        // Last 5 leave requests
        LeaveRequest::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->each(function ($l) use (&$activities) {
                $activities->push([
                    'type'        => 'leave',
                    'description' => 'Pengajuan ' . strtoupper($l->type) . ' — ' . ucfirst($l->status),
                    'timestamp'   => $l->created_at?->toIso8601String(),
                ]);
            });

        // Last 5 expense claims
        ExpenseClaim::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->each(function ($e) use (&$activities) {
                $activities->push([
                    'type'        => 'expense',
                    'description' => 'Klaim ' . ucfirst($e->category) . ' — Rp ' . number_format($e->amount, 0, ',', '.'),
                    'timestamp'   => $e->created_at?->toIso8601String(),
                ]);
            });

        return $activities
            ->sortByDesc('timestamp')
            ->values()
            ->take(5)
            ->all();
    }
}
