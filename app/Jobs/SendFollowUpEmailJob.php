<?php

namespace App\Jobs;

use App\Mail\ShipmentFollowUpMail;
use App\Models\Invoice;
use App\Models\Testimonial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFollowUpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 300;

    public function __construct(public Invoice $invoice) {}

    public function handle(): void
    {
        $invoice  = $this->invoice->load(['customer.user', 'shipment']);
        $customer = $invoice->customer;

        if (!$customer || !$customer->user?->email) {
            Log::warning("SendFollowUpEmailJob: no email for invoice {$invoice->invoice_number}");
            return;
        }

        if ($customer->no_followup_email) {
            return;
        }

        if ($invoice->follow_up_sent_at) {
            return;
        }

        $testimonial = Testimonial::firstOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'customer_id'     => $customer->id,
                'display_name'    => $customer->company_name ?? '',
                'company_name'    => $customer->company_name ?? '',
                'token'           => Testimonial::generateToken(),
                'token_expires_at'=> now()->addDays(90),
                'rating'          => 5,
                'content'         => '',
                'status'          => 'pending',
                'ip_address'      => '0.0.0.0',
            ]
        );
        $token = $testimonial->token;

        Mail::to($customer->user->email)
            ->send(new ShipmentFollowUpMail($invoice, $token));

        $invoice->update(['follow_up_sent_at' => now()]);

        Log::info("SendFollowUpEmailJob: follow-up sent to {$customer->user->email} (invoice {$invoice->invoice_number})");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendFollowUpEmailJob failed for invoice {$this->invoice->invoice_number}: {$e->getMessage()}");
    }
}
