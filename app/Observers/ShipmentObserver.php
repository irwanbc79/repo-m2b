<?php

namespace App\Observers;

use App\Jobs\SendFollowUpEmailJob;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Log;

class ShipmentObserver
{
    public function updating(Shipment $shipment): void
    {
        if (!$shipment->isDirty('status')) {
            return;
        }

        $oldStatus = $shipment->getOriginal('status');
        $newStatus = $shipment->status;

        if ($newStatus !== Shipment::STATUS_COMPLETED || $oldStatus === Shipment::STATUS_COMPLETED) {
            return;
        }

        $invoice = $shipment->invoices()
            ->where('status', 'paid')
            ->whereNull('follow_up_sent_at')
            ->whereHas('customer', fn($q) => $q->where('no_followup_email', false))
            ->whereHas('customer.user')
            ->first();

        if (!$invoice) {
            Log::info("ShipmentObserver: shipment {$shipment->id} completed — no eligible invoice.");
            return;
        }

        $customerId = $invoice->customer_id;

        // Jangan kirim jika customer sudah punya testimoni yang disetujui
        if (Testimonial::where('customer_id', $customerId)->where('status', 'approved')->exists()) {
            Log::info("ShipmentObserver: skip — customer {$customerId} sudah punya testimoni approved.");
            return;
        }

        // Kebijakan "sekali seumur hidup": jangan email lagi jika customer PERNAH di-follow-up.
        // Klien sering-shipment tidak di-spam; ajakan lanjut lewat CTA in-portal (pasif).
        $everFollowedUp = Invoice::where('customer_id', $customerId)
            ->whereNotNull('follow_up_sent_at')
            ->where('id', '!=', $invoice->id)
            ->exists();

        if ($everFollowedUp) {
            Log::info("ShipmentObserver: skip — customer {$customerId} sudah pernah di-follow-up (kebijakan sekali).");
            return;
        }

        SendFollowUpEmailJob::dispatch($invoice)->delay(now()->addDays(3));

        Log::info("ShipmentObserver: follow-up job dijadwalkan 3 hari untuk invoice {$invoice->invoice_number}.");
    }
}
