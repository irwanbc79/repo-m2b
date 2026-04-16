<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Upload signed document (via token, no auth required)
     */
    public function uploadDocument(Request $request, string $token)
    {
        $quotation = Quotation::where('approval_token', $token)->firstOrFail();

        if ($quotation->approval_status !== 'approved') {
            return redirect()->route('quotation.approve', $token)
                ->with('upload_error', 'Dokumen hanya bisa diupload setelah penawaran disetujui.');
        }

        $request->validate([
            'signed_document' => 'required|file|mimes:pdf|max:5120',
        ], [
            'signed_document.required' => 'Pilih file PDF terlebih dahulu.',
            'signed_document.mimes'    => 'File harus berformat PDF.',
            'signed_document.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        // Delete old file if exists
        if ($quotation->signed_document_path) {
            Storage::disk('public')->delete($quotation->signed_document_path);
        }

        $path = $request->file('signed_document')->storeAs(
            'signed-quotations',
            'QT-' . $quotation->id . '-' . now()->format('YmdHis') . '.pdf',
            'public'
        );

        $quotation->update([
            'signed_document_path' => $path,
            'signed_document_at'   => now(),
        ]);

        return view('quotation.approval-done', compact('quotation'))
            ->with('upload_success', 'Dokumen berhasil diupload. Tim M2B akan segera memverifikasi.');
    }
}
