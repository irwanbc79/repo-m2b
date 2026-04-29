<?php

namespace App\Livewire\Admin;

use App\Models\TaxNote;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TaxNoteManagement extends Component
{
    use WithPagination;

    public $perPage = 25;

    // Form fields
    public $isModalOpen = false;
    public $isEditing   = false;
    public $editingId   = null;
    public $periode     = '';
    public $catatan     = '';

    // Delete confirm
    public $showDeleteConfirm = false;
    public $deleteId          = null;

    protected function rules(): array
    {
        return [
            'periode' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'catatan' => 'required|string|min:5|max:5000',
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
        $this->reset(['isEditing', 'editingId', 'catatan']);
        $this->periode    = now()->format('Y-m');
        $this->isModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $note = TaxNote::findOrFail($id);
        abort_unless($this->canEdit($note), 403);

        $this->isEditing   = true;
        $this->editingId   = $id;
        $this->periode     = $note->periode;
        $this->catatan     = $note->catatan;
        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            $note = TaxNote::findOrFail($this->editingId);
            abort_unless($this->canEdit($note), 403);
            $note->update(['periode' => $this->periode, 'catatan' => $this->catatan]);
            session()->flash('success', 'Catatan pajak berhasil diperbarui.');
        } else {
            abort_unless($this->canCreate(), 403);
            TaxNote::create([
                'user_id' => Auth::id(),
                'periode' => $this->periode,
                'catatan' => $this->catatan,
            ]);
            session()->flash('success', 'Catatan pajak berhasil disimpan.');
        }

        $this->isModalOpen = false;
        $this->reset(['isEditing', 'editingId', 'periode', 'catatan']);
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
        TaxNote::findOrFail($this->deleteId)->delete();
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
        $this->reset(['isEditing', 'editingId', 'periode', 'catatan']);
    }

    // --- Render ---

    public function render()
    {
        $notes = TaxNote::with('user')
            ->orderByDesc('periode')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.admin.tax-note-management', [
            'notes'          => $notes,
            'periodeOptions' => $this->periodeOptions(),
        ])->layout('layouts.admin');
    }
}
