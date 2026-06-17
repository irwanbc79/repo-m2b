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

    public $filterStatus = 'pending_filled';
    public $adminNote = '';
    public $activeId = null;

    // Inline edit properties
    public $editingId = null;
    public $editDisplayName = '';
    public $editCompanyName = '';
    public $editPosition = '';
    public $editRating = 5;
    public $editContent = '';

    public function startEdit($id)
    {
        $t = Testimonial::findOrFail($id);
        $this->editingId = $t->id;
        $this->editDisplayName = $t->display_name;
        $this->editCompanyName = $t->company_name;
        $this->editPosition = $t->position;
        $this->editRating = $t->rating;
        $this->editContent = $t->content ?? '';
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->resetEditFields();
    }

    private function resetEditFields()
    {
        $this->editDisplayName = '';
        $this->editCompanyName = '';
        $this->editPosition = '';
        $this->editRating = 5;
        $this->editContent = '';
    }

    public function saveEdit()
    {
        $this->validate([
            'editDisplayName' => 'required|string|max:100',
            'editCompanyName' => 'nullable|string|max:150',
            'editPosition'     => 'nullable|string|max:100',
            'editRating'       => 'required|integer|min:1|max:5',
            'editContent'      => 'required|string|min:10|max:1000',
        ], [
            'editDisplayName.required' => 'Nama PIC wajib diisi.',
            'editContent.required' => 'Isi testimoni wajib diisi.',
            'editContent.min' => 'Testimoni minimal 10 karakter.',
        ]);

        $t = Testimonial::findOrFail($this->editingId);
        $t->update([
            'display_name' => $this->editDisplayName,
            'company_name' => $this->editCompanyName,
            'position'     => $this->editPosition,
            'rating'       => $this->editRating,
            'content'      => $this->editContent,
        ]);

        $this->editingId = null;
        $this->resetEditFields();
        session()->flash('message', 'Testimoni berhasil diperbarui.');
    }

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
            ->when($this->filterStatus === 'pending_filled', function($q) {
                $q->pending()->whereNotNull('content')->where('content', '!=', '');
            })
            ->when($this->filterStatus === 'pending_unfilled', function($q) {
                $q->pending()->where(function($query) {
                    $query->whereNull('content')->orWhere('content', '');
                });
            })
            ->when(in_array($this->filterStatus, ['approved', 'rejected']), function($q) {
                $q->where('status', $this->filterStatus);
            })
            ->latest()
            ->paginate(20);

        $counts = [
            'pending_filled'   => Testimonial::pending()->whereNotNull('content')->where('content', '!=', '')->count(),
            'pending_unfilled' => Testimonial::pending()->where(function($q) {
                                      $q->whereNull('content')->orWhere('content', '');
                                  })->count(),
            'approved'         => Testimonial::approved()->count(),
            'rejected'         => Testimonial::where('status', 'rejected')->count(),
        ];

        return view('livewire.admin.testimonial-moderation', compact('testimonials', 'counts'))
            ->layout('layouts.admin');
    }
}
