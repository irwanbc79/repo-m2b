<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollSlip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json([
                    'success' => true,
                    'message' => 'Data karyawan tidak ditemukan. Rekap gaji belum tersedia untuk akun ini.',
                'data'    => [],
                    'employee_missing' => true,
            ]);
        }

        $slips = PayrollSlip::with(['period', 'deductionItems'])
            ->where('employee_id', $employee->id)
            ->orderByDesc(function ($q) {
                $q->select('tahun')
                  ->from('payroll_periods')
                  ->whereColumn('payroll_periods.id', 'payroll_slips.period_id');
            })
            ->get()
            ->sortByDesc(fn($s) => $s->period->tahun * 100 + $s->period->bulan)
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $slips->map(fn($slip) => $this->formatSlip($slip)),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        $slip = PayrollSlip::with(['period', 'deductionItems', 'employee.jabatan'])
            ->where('employee_id', $employee->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->formatSlip($slip, detail: true),
        ]);
    }

    private function formatSlip(PayrollSlip $slip, bool $detail = false): array
    {
        $base = [
            'id'                  => $slip->id,
            'periode'             => $slip->period->period_label ?? '-',
            'bulan'               => $slip->period->bulan ?? 0,
            'tahun'               => $slip->period->tahun ?? 0,
            'status'              => $slip->period->status ?? 'draft',
            'gaji_pokok'          => (float) $slip->gaji_pokok,
            'tunjangan_transport' => (float) $slip->tunjangan_transport,
            'tunjangan_jabatan'   => (float) $slip->tunjangan_jabatan,
            'tunjangan_lainnya'   => (float) $slip->tunjangan_lainnya,
            'lembur_jam'          => (float) $slip->lembur_jam,
            'lembur_nominal'      => (float) $slip->lembur_nominal,
            'potongan_bpjs_kes'   => (float) $slip->potongan_bpjs_kes,
            'potongan_bpjs_tk'    => (float) $slip->potongan_bpjs_tk,
            'potongan_lainnya'    => (float) $slip->potongan_lainnya,
            'total_gaji'          => (float) $slip->total_gaji,
            'catatan'             => $slip->catatan,
        ];

        if ($detail) {
            $base['potongan_items'] = $slip->deductionItems->map(fn($item) => [
                'nama'    => $item->nama_potongan,
                'nominal' => (float) $item->nominal,
                'catatan' => $item->catatan,
            ])->values();
            $base['jabatan'] = $slip->employee->jabatan->nama_jabatan ?? '-';
        }

        return $base;
    }
}
