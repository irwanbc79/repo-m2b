<?php

namespace App\Livewire\Admin;

use App\Mail\TestimonialApprovedMail;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class TestimonialModeration extends Component
{
    use WithPagination;

    public $filterStatus = 'pending';
    public $adminNote = '';
    public $activeId = null;

    public function approve($id)
    {
        $t = Testimonial::findOrFail($id);
        $t->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Kirim email notifikasi + ajak Google Review
        try {
            $email = $t->customer?->user?->email;
            if ($email) {
                Mail::to($email)->send(new TestimonialApprovedMail($t));
            }
        } catch (\Throwable) {}

        session()->flash('message', 'Testimoni disetujui dan email notifikasi dikirim.');
    }

    public function reject($id)
    {
        Testimonial::findOrFail($id)->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_note'  => $this->adminNote,
        ]);

        $this->adminNote = '';
        $this->activeId  = null;
        session()->flash('message', 'Testimoni ditolak.');
    }

    public function render()
    {
        $testimonials = Testimonial::with(['customer.user'])
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(20);

        $counts = [
            'pending'  => Testimonial::pending()->count(),
            'approved' => Testimonial::approved()->count(),
            'rejected' => Testimonial::where('status', 'rejected')->count(),
        ];

        return view('livewire.admin.testimonial-moderation', compact('testimonials', 'counts'))
            ->layout('layouts.admin');
    }
}
