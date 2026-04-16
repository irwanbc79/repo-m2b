<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;

class QuotationApprovalController extends Controller
{
    /**
     * Tampilkan halaman konfirmasi approval (publik, via token)
     */
    public function show(Request $request, string $token)
    {
        $quotation = Quotation::where('approval_token', $token)
            ->with(['customer', 'items'])
            ->firstOrFail();

        // Sudah diproses sebelumnya
        if ($quotation->approval_status !== 'pending') {
            return view('quotation.approval-done', compact('quotation'));
        }

        // Expired
        if ($quotation->valid_until->isPast()) {
            return view('quotation.approval-expired', compact('quotation'));
        }

        $action = $request->query('action'); // 'approve' atau 'reject' dari link email

        return view('quotation.approve', compact('quotation', 'action'));
    }

    /**
     * Proses keputusan customer (approve / reject)
     */
    public function process(Request $request, string $token)
    {
        $quotation = Quotation::where('approval_token', $token)->firstOrFail();

        if ($quotation->approval_status !== 'pending') {
            return redirect()->route('quotation.approve', $token);
        }

        if ($quotation->valid_until->isPast()) {
            return redirect()->route('quotation.approve', $token);
        }

        $action = $request->input('action');

        if (!in_array($action, ['approve', 'reject'])) {
            return back()->withErrors(['action' => 'Pilihan tidak valid.']);
        }

        $quotation->update([
            'approval_status'    => $action === 'approve' ? 'approved' : 'rejected',
            'status'             => $action === 'approve' ? 'accepted'  : 'rejected',
            'approved_at'        => now(),
            'approved_by'        => $quotation->manual_pic
                                    ?? ($quotation->customer->company_name ?? 'Customer'),
            'approval_ip'        => $request->ip(),
            'approval_user_agent'=> $request->userAgent(),
        ]);

        return view('quotation.approval-done', compact('quotation'));
    }
}
