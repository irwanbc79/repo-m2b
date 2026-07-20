<?php

namespace App\Livewire\Customer;

use App\Mail\TestimonialPendingMail;
use App\Models\Shipment;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

/**
 * Pengisian testimoni LANGSUNG di portal (customer login) — melengkapi/menggantikan
 * alur link email yang konversinya rendah. Bila customer sudah punya record
 * "belum diisi" dari follow-up, form ini mengisi record itu (tetap tertaut invoice).
 */
class TestimonialForm extends Component
{
    public string $state = 'form'; // form | review | approved | locked
    public ?int $testimonialId = null;

    public string $display_name = '';
    public string $company_name = '';
    public string $position = '';
    public int $rating = 5;
    public string $content = '';

    public function mount()
    {
        $customer = Auth::user()->customer;
        if (! $customer) {
            $this->state = 'locked';
            return;
        }

        // Gate: hanya customer dengan minimal 1 shipment selesai.
        $hasCompleted = Shipment::where('customer_id', $customer->id)
            ->where('status', 'completed')->exists();

        // Testimoni terakhir yang relevan (bukan yang ditolak).
        $existing = Testimonial::where('customer_id', $customer->id)
            ->where('status', '!=', 'rejected')
            ->latest()->first();

        if ($existing && $existing->status === 'approved') {
            $this->state = 'approved';
            $this->fillFrom($existing);
            return;
        }
        if ($existing && $existing->isFilled()) {
            $this->state = 'review'; // sudah diisi, menunggu moderasi
            $this->fillFrom($existing);
            return;
        }

        if (! $hasCompleted) {
            $this->state = 'locked';
            return;
        }

        // Ada record kosong dari follow-up → isi record itu; else siap buat baru.
        if ($existing) {
            $this->testimonialId = $existing->id;
            $this->fillFrom($existing);
        }
        $this->display_name = $this->display_name ?: (Auth::user()->name ?: $customer->company_name ?? '');
        $this->company_name = $this->company_name ?: ($customer->company_name ?? '');
        $this->position     = $this->position ?: ($customer->position ?? '');
        $this->state = 'form';
    }

    private function fillFrom(Testimonial $t): void
    {
        $this->testimonialId = $t->id;
        $this->display_name = $t->display_name ?: '';
        $this->company_name = $t->company_name ?: '';
        $this->position     = $t->position ?: '';
        $this->rating       = $t->rating ?: 5;
        $this->content      = $t->content ?: '';
    }

    public function submit()
    {
        $this->validate([
            'display_name' => 'required|string|max:100',
            'company_name' => 'nullable|string|max:150',
            'position'     => 'nullable|string|max:100',
            'rating'       => 'required|integer|min:1|max:5',
            'content'      => 'required|string|min:10|max:1000',
        ], [
            'display_name.required' => 'Nama Anda wajib diisi.',
            'content.required' => 'Isi testimoni wajib diisi.',
            'content.min' => 'Testimoni minimal 10 karakter.',
        ]);

        $customer = Auth::user()->customer;
        if (! $customer) {
            $this->state = 'locked';
            return;
        }

        $data = [
            'display_name' => $this->display_name,
            'company_name' => $this->company_name,
            'position'     => $this->position,
            'rating'       => $this->rating,
            'content'      => $this->content,
            'status'       => 'pending',
            'ip_address'   => request()->ip(),
        ];

        if ($this->testimonialId) {
            $t = Testimonial::find($this->testimonialId);
            $t?->update($data);
        } else {
            $t = Testimonial::create(array_merge($data, [
                'customer_id'      => $customer->id,
                'token'            => Testimonial::generateToken(),
                'token_expires_at' => now()->addDays(90),
            ]));
            $this->testimonialId = $t->id;
        }

        try {
            Mail::to(config('mail.from.address'))->send(new TestimonialPendingMail($t->fresh()));
        } catch (\Throwable $e) {
            Log::warning('TestimonialForm notify admin gagal: ' . $e->getMessage());
        }

        $this->state = 'review';
        session()->flash('message', 'Terima kasih! Testimoni Anda terkirim dan sedang ditinjau tim M2B.');
    }

    public function render()
    {
        return view('livewire.customer.testimonial-form')->layout('layouts.customer');
    }
}
