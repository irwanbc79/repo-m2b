<?php

namespace App\Livewire\Admin\HRD;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Auth;

class JabatanManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;

    // Modal state
    public $isModalOpen = false;
    public $isEditing = false;
    public $jabatanId = null;

    // Form fields
    public $nama_jabatan = '';
    public $gaji_pokok_default = 0;
    public $tunjangan_jabatan_default = 0;

    // Delete confirm
    public $showDeleteConfirm = false;
    public $deleteId = null;

    protected $queryString = ['search'];

    protected function rules(): array
    {
        return [
            'nama_jabatan'               => 'required|string|max:100',
            'gaji_pokok_default'         => 'required|numeric|min:0',
            'tunjangan_jabatan_default'  => 'required|numeric|min:0',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // --- Access Control ---
    public function canViewAny(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'director', 'finance', 'konsultan_pajak']);
    }

    public function canCreate(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'finance']);
    }

    public function canEdit(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'finance']);
    }

    public function canDelete(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin']);
    }

    // --- CRUD ---
    public function openCreate(): void
    {
        abort_unless($this->canCreate(), 403);
        $this->reset(['jabatanId', 'nama_jabatan', 'gaji_pokok_default', 'tunjangan_jabatan_default']);
        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canEdit(), 403);
        $jabatan = Jabatan::findOrFail($id);
        $this->jabatanId = $jabatan->id;
        $this->nama_jabatan = $jabatan->nama_jabatan;
        $this->gaji_pokok_default = $jabatan->gaji_pokok_default;
        $this->tunjangan_jabatan_default = $jabatan->tunjangan_jabatan_default;
        $this->isEditing = true;
        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            abort_unless($this->canEdit(), 403);
            Jabatan::findOrFail($this->jabatanId)->update([
                'nama_jabatan'               => $this->nama_jabatan,
                'gaji_pokok_default'         => $this->gaji_pokok_default,
                'tunjangan_jabatan_default'  => $this->tunjangan_jabatan_default,
            ]);
            session()->flash('success', 'Jabatan berhasil diperbarui.');
        } else {
            abort_unless($this->canCreate(), 403);
            Jabatan::create([
                'nama_jabatan'               => $this->nama_jabatan,
                'gaji_pokok_default'         => $this->gaji_pokok_default,
                'tunjangan_jabatan_default'  => $this->tunjangan_jabatan_default,
            ]);
            session()->flash('success', 'Jabatan berhasil ditambahkan.');
        }

        $this->isModalOpen = false;
        $this->reset(['jabatanId', 'nama_jabatan', 'gaji_pokok_default', 'tunjangan_jabatan_default']);
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canDelete(), 403);
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        abort_unless($this->canDelete(), 403);
        Jabatan::findOrFail($this->deleteId)->delete();
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
        session()->flash('success', 'Jabatan berhasil dihapus.');
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->showDeleteConfirm = false;
    }

    public function render()
    {
        $jabatanList = Jabatan::query()
            ->when($this->search, fn($q) => $q->where('nama_jabatan', 'like', "%{$this->search}%"))
            ->orderBy('nama_jabatan')
            ->paginate($this->perPage);

        return view('livewire.admin.hrd.jabatan-management', [
            'jabatanList' => $jabatanList,
        ])->layout('layouts.admin');
    }
}
