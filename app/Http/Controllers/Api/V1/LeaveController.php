<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function __construct(private FonnteService $fonnte) {}

    public function index(Request $request): JsonResponse
    {
        $leaves = LeaveRequest::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan cuti/izin.',
            'data'    => $leaves,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type'          => 'required|in:sakit,izin,cuti,dinas',
            'start_date'    => 'required|date|after_or_equal:today',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'required|string|max:1000',
            'document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('leaves/documents', 'public');
        }

        $leave = LeaveRequest::create([
            'user_id'       => $request->user()->id,
            'type'          => $request->type,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'reason'        => $request->reason,
            'document_path' => $documentPath,
            'status'        => 'pending',
        ]);

        $this->notifyManagerNewLeave($leave, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dikirim.',
            'data'    => $leave,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $leave = LeaveRequest::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengajuan.',
            'data'    => $leave->load('approver:id,name'),
        ]);
    }

    // ─── WA Notifications ───────────────────────────────────────────────────────

    private function notifyManagerNewLeave(LeaveRequest $leave, User $employee): void
    {
        $managers = User::where(function ($q) {
            $q->whereJsonContains('roles', 'manager')
              ->orWhereJsonContains('roles', 'admin')
              ->orWhere('role', 'manager')
              ->orWhere('role', 'admin');
        })->whereNotNull('phone')->get();

        $typeLabel  = strtoupper($leave->type);
        $start      = $leave->start_date->format('d/m/Y');
        $end        = $leave->end_date->format('d/m/Y');
        $message    = "Pengajuan {$typeLabel} dari *{$employee->name}* untuk tanggal {$start} - {$end}. Alasan: {$leave->reason}. Mohon review di portal.";

        foreach ($managers as $manager) {
            $this->fonnte->send($manager->phone, $message);
        }
    }

    public function notifyEmployeeStatusUpdate(LeaveRequest $leave): void
    {
        $employee = $leave->user;
        if (! $employee?->phone) {
            return;
        }

        $approverName = $leave->approver?->name ?? 'Admin';
        $typeLabel    = strtoupper($leave->type);

        if ($leave->status === 'approved') {
            $message = "✅ *Disetujui*: Pengajuan {$typeLabel} kamu telah disetujui oleh {$approverName}.";
        } else {
            $message = "❌ *Ditolak*: Pengajuan {$typeLabel} kamu telah ditolak oleh {$approverName}.";
        }

        $this->fonnte->send($employee->phone, $message);
    }
}
