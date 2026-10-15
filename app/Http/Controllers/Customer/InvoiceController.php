<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;

class InvoiceController extends Controller
{
    public function preview(Invoice $invoice)
    {
        $user = auth()->user();
        $customerId = $user?->getCustomerId();

        abort_if(
            !$customerId || (int) $invoice->customer_id !== (int) $customerId,
            403,
            'Unauthorized invoice access'
        );

        $invoice->load([
            'customer',
            'shipment',
            'items'
        ]);

        return view('admin.invoice-print', [
            'invoice' => $invoice,
            'isCustomerView' => true
        ]);
    }

    public function sendInvoiceEmail(Request $request, $id)
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->isAdminLevel() || $user->hasPermission('invoice.send')),
            403,
            'Unauthorized invoice email action'
        );

        $invoice = Invoice::with(['items', 'customer', 'shipment'])->findOrFail($id);

    // 1. GENERATE PDF (WAJIB)
    $pdf = Pdf::loadView('admin.invoices.invoice-print', [
        'invoice' => $invoice,
        'isPdf'   => true, // FLAG PENTING
    ])->setPaper('A4');

    // 2. KIRIM EMAIL
    Mail::to($request->email)
        ->send(new InvoiceMail($invoice, $pdf));

    return response()->json([
        'status'  => 'ok',
        'message' => 'Invoice berhasil dikirim.',
    ]);
}

}
