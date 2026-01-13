<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;

class InvoiceController extends Controller
{
    public function preview(Invoice $invoice)
{
    $customer = auth()->user()->customer;

    abort_if(
        !$customer || $invoice->customer_id !== $customer->id,
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
