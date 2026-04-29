<?php

namespace App\Livewire\Admin;

use App\Models\Invoice;
use App\Models\TaxNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TaxNoteManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $perPage = 25;

    // Form fields
    public $isModalOpen  = false;
    public $isEditing    = false;
    public $editingId    = null;
    public $periode      = '';
    public $catatan      = '';
    public $invoice_id   = null;
    public $jenis_pajak  = '';
    public $nominal      = '';

    // Attachments
    public $newAttachments      = [];
    public $existingAttachments = [];
    public $removedAttachments  = [];

    // Invoice search
    public $invoiceSearch = '';

    // Delete confirm
    public $showDeleteConfirm = false;
    public $deleteId          = null;

    protected function rules(): array
    {
        return [
            'periode'          => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'catatan'          => 'required|string|min:5|max:5000',
            'invoice_id'       => 'nullable|exists:invoices,id',
            'jenis_pajak'      => 'nullable|string|max:50',
            'nominal'          => 'nullable|numeric|min:0',
            'newAttachments.*' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx',
        ];
    }

    // --- Access Control ---

    public function canViewAny(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'director', 'finance', 'konsultan_pajak']);
    }

    public function canCreate(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'finance', 'konsultan_pajak']);
    }

    public function canEdit(TaxNote $note): bool
    {
        $user = Auth::user();
        return $user->hasRole(['admin', 'super_admin']) || $note->user_id === $user->id;
    }

    public function canDelete(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin']);
    }

    // --- Mount ---

    public function mount(): void
    {
        abort_unless($this->canViewAny(), 403, 'Anda tidak memiliki akses ke halaman ini.');
        $this->periode = now()->format('Y-m');
    }

    // --- Helpers ---

    private function periodeOptions(): array
    {
        $options = [];
        for ($i = 0; $i < 12; $i++) {
            $options[] = now()->subMonths($i)->format('Y-m');
        }
        return $options;
    }

    // --- Actions ---

    public function openCreate(): void
    {
        abort_unless($this->canCreate(), 403);
        $this->reset(['isEditing', 'editingId', 'catatan', 'invoice_id', 'jenis_pajak',
                      'nominal', 'invoiceSearch', 'newAttachments', 'existingAttachments', 'removedAttachments']);
        $this->periode    = now()->format('Y-m');
        $this->isModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $note = TaxNote::findOrFail($id);
        abort_unless($this->canEdit($note), 403);

        $this->isEditing          = true;
        $this->editingId          = $id;
        $this->periode            = $note->periode;
        $this->catatan            = $note->catatan;
        $this->invoice_id         = $note->invoice_id;
        $this->jenis_pajak        = $note->jenis_pajak ?? '';
        $this->nominal            = $note->nominal ?? '';
        $this->existingAttachments = $note->attachments ?? [];
        $this->removedAttachments  = [];
        $this->newAttachments      = [];
        $this->isModalOpen = true;
    }

    public function removeExistingAttachment(int $index): void
    {
        if (isset($this->existingAttachments[$index])) {
            $this->removedAttachments[] = $this->existingAttachments[$index];
            array_splice($this->existingAttachments, $index, 1);
        }
    }

    public function removeNewAttachment(int $index): void
    {
        array_splice($this->newAttachments, $index, 1);
    }

    public function save(): void
    {
        $this->validate();

        // Delete files the user removed from existing list
        foreach ($this->removedAttachments as $path) {
            Storage::disk('public')->delete($path);
        }

        // Store newly uploaded files
        $storedPaths = $this->existingAttachments;
        foreach ($this->newAttachments as $file) {
            $storedPaths[] = $file->store('tax-notes', 'public');
        }

        $data = [
            'periode'     => $this->periode,
            'catatan'     => $this->catatan,
            'invoice_id'  => $this->invoice_id  ?: null,
            'jenis_pajak' => $this->jenis_pajak  ?: null,
            'nominal'     => $this->nominal !== '' ? $this->nominal : null,
            'attachments' => !empty($storedPaths) ? array_values($storedPaths) : null,
        ];

        if ($this->isEditing) {
            $note = TaxNote::findOrFail($this->editingId);
            abort_unless($this->canEdit($note), 403);
            $note->update($data);
            session()->flash('success', 'Catatan pajak berhasil diperbarui.');
        } else {
            abort_unless($this->canCreate(), 403);
            TaxNote::create(array_merge($data, ['user_id' => Auth::id()]));
            session()->flash('success', 'Catatan pajak berhasil disimpan.');
        }

        $this->isModalOpen = false;
        $this->reset(['isEditing', 'editingId', 'periode', 'catatan', 'invoice_id', 'jenis_pajak',
                      'nominal', 'invoiceSearch', 'newAttachments', 'existingAttachments', 'removedAttachments']);
    }

    public function toggleResolved(int $id): void
    {
        $note = TaxNote::findOrFail($id);
        abort_unless($this->canEdit($note), 403);

        if ($note->is_resolved) {
            $note->update(['is_resolved' => false, 'resolved_at' => null]);
        } else {
            $note->update(['is_resolved' => true, 'resolved_at' => now()]);
        }
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canDelete(), 403);
        $this->deleteId          = $id;
        $this->showDeleteConfirm = true;
    }

    public function deleteNote(): void
    {
        abort_unless($this->canDelete(), 403);
        $note = TaxNote::findOrFail($this->deleteId);

        // Delete all attachments from storage
        foreach (($note->attachments ?? []) as $path) {
            Storage::disk('public')->delete($path);
        }

        $note->delete();
        $this->reset(['deleteId', 'showDeleteConfirm']);
        session()->flash('success', 'Catatan pajak berhasil dihapus.');
    }

    public function cancelDelete(): void
    {
        $this->reset(['deleteId', 'showDeleteConfirm']);
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->reset(['isEditing', 'editingId', 'periode', 'catatan', 'invoice_id', 'jenis_pajak',
                      'nominal', 'invoiceSearch', 'newAttachments', 'existingAttachments', 'removedAttachments']);
    }

    // --- Render ---

    public function render()
    {
        $notes = TaxNote::with(['user', 'invoice.customer'])
            ->orderByDesc('periode')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $invSearch = trim($this->invoiceSearch);
        $invoiceOptions = Invoice::query()
            ->with('customer:id,company_name')
            ->when($invSearch, fn($q) =>
                $q->where('invoice_number', 'like', "%{$invSearch}%")
                  ->orWhereHas('customer', fn($q2) =>
                      $q2->where('company_name', 'like', "%{$invSearch}%")
                  )
            )
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($i) => [
                'id'    => $i->id,
                'label' => implode(' · ', array_filter([
                    $i->invoice_number,
                    $i->customer?->company_name,
                    strtoupper($i->type ?? ''),
                    $i->status ? strtoupper($i->status) : null,
                ])),
            ]);

        return view('livewire.admin.tax-note-management', [
            'notes'          => $notes,
            'periodeOptions' => $this->periodeOptions(),
            'invoiceOptions' => $invoiceOptions,
            'jenisPajakList' => TaxNote::JENIS_PAJAK,
        ])->layout('layouts.admin');
    }
}
