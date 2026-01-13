<?php

namespace App\Services\Business;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class InvoiceWorkflowService
{
    public function generateCommercialFromProforma(Invoice $proforma): Invoice
    {
        if (!$proforma->isProforma()) {
            throw new Exception("Invoice must be a Proforma invoice.");
        }

        if (!$proforma->isPaid()) {
            throw new Exception("Proforma invoice must be paid before generating Commercial invoice.");
        }

        if ($proforma->hasCommercialInvoice()) {
            throw new Exception("Commercial invoice already exists for this Proforma.");
        }

        DB::beginTransaction();

        try {
            $commercial = $this->createCommercialInvoice($proforma);
            $this->copyInvoiceItems($proforma, $commercial);
            $commercial->related_invoice_id = $proforma->id;
            $commercial->save();

            DB::commit();
            return $commercial;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to generate Commercial invoice: " . $e->getMessage());
        }
    }

    protected function createCommercialInvoice(Invoice $proforma): Invoice
    {
        $commercialNumber = $this->generateCommercialNumber();

        return Invoice::create([
            'customer_id' => $proforma->customer_id,
            'shipment_id' => $proforma->shipment_id,
            'invoice_number' => $commercialNumber,
            'type' => 'commercial',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'unpaid',
            'subtotal' => $proforma->subtotal,
            'discount_percentage' => $proforma->discount_percentage,
            'discount_amount' => $proforma->discount_amount,
            'service_total' => $proforma->service_total,
            'reimbursement_total' => $proforma->reimbursement_total,
            'tax_amount' => $proforma->tax_amount,
            'pph_amount' => $proforma->pph_amount,
            'down_payment' => $proforma->grand_total,
            'grand_total' => 0,
            'notes' => $proforma->notes,
            'related_invoice_id' => $proforma->id,
        ]);
    }

    protected function copyInvoiceItems(Invoice $proforma, Invoice $commercial): void
    {
        foreach ($proforma->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $commercial->id,
                'item_type' => $item->item_type,
                'description' => $item->description,
                'qty' => $item->qty,
                'price' => $item->price,
                'total' => $item->total,
            ]);
        }

        $commercial->refresh();
        $totalBeforeDP = $commercial->subtotal + $commercial->service_total 
                       + $commercial->reimbursement_total + $commercial->tax_amount 
                       - $commercial->discount_amount;

        $commercial->grand_total = max(0, $totalBeforeDP - $commercial->down_payment);
        $commercial->save();
    }

    protected function generateCommercialNumber(): string
    {
        $prefix = 'INV';
        $year = date('y');
        $month = date('m');

        $latest = Invoice::where('type', 'commercial')
                        ->where('invoice_number', 'like', "{$prefix}/{$year}{$month}/%")
                        ->orderBy('invoice_number', 'desc')
                        ->first();

        if ($latest) {
            preg_match('/(\d+)$/', $latest->invoice_number, $matches);
            $sequence = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s/%s%s/%04d', $prefix, $year, $month, $sequence);
    }

    public function generateProformaNumber(): string
    {
        $prefix = 'PRO';
        $year = date('y');
        $month = date('m');

        $latest = Invoice::where('type', 'proforma')
                        ->where('invoice_number', 'like', "{$prefix}/{$year}{$month}/%")
                        ->orderBy('invoice_number', 'desc')
                        ->first();

        if ($latest) {
            preg_match('/(\d+)$/', $latest->invoice_number, $matches);
            $sequence = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s/%s%s/%04d', $prefix, $year, $month, $sequence);
    }

    public function getWorkflowStatus(Invoice $invoice): array
    {
        $status = [
            'type' => $invoice->type,
            'status' => $invoice->status,
            'can_generate_commercial' => false,
            'has_commercial' => false,
            'commercial_invoice' => null,
            'proforma_invoice' => null,
        ];

        if ($invoice->isProforma()) {
            $status['can_generate_commercial'] = $invoice->canGenerateCommercial();
            $status['has_commercial'] = $invoice->hasCommercialInvoice();
            
            if ($status['has_commercial']) {
                $status['commercial_invoice'] = $invoice->derivedInvoices()
                    ->where('type', 'commercial')
                    ->first();
            }
        }

        if ($invoice->isCommercial() && $invoice->related_invoice_id) {
            $status['proforma_invoice'] = $invoice->relatedInvoice;
        }

        return $status;
    }

    public function cancelInvoice(Invoice $invoice): bool
    {
        if ($invoice->isPaid()) {
            throw new Exception("Cannot cancel a paid invoice.");
        }

        DB::beginTransaction();

        try {
            if ($invoice->isCommercial()) {
                $invoice->status = 'cancelled';
                $invoice->save();
            }

            if ($invoice->isProforma() && $invoice->hasCommercialInvoice()) {
                $commercial = $invoice->derivedInvoices()
                    ->where('type', 'commercial')
                    ->first();

                if ($commercial && $commercial->isPaid()) {
                    throw new Exception("Cannot cancel Proforma because Commercial invoice is already paid.");
                }

                if ($commercial) {
                    $commercial->status = 'cancelled';
                    $commercial->save();
                }

                $invoice->status = 'cancelled';
                $invoice->save();
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
