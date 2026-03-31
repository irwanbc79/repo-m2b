<?php

namespace App\Livewire\Admin\HRD;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Employee;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Auth;

class EmployeeManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $perPage = 25;

    // Modal state
    public $isModalOpen = false;
    public $isEditing = false;
    public $employeeId = null;

    // Form fields
    public $nik = '';
    public $nama = '';
    public $jabatan_id = '';
    public $join_date = '';
    public $status = 'active';
    public $employment_type = 'permanent';
    public $no_hp = '';
    public $alamat = '';
    public $user_id = null;

    // Delete confirm
    public $showDeleteConfirm = false;
    public $deleteId = null;

    protected $queryString = ['search', 'filterStatus'];

    protected function rules(): array
    {
        $nikRule = $this->isEditing
            ? 'required|string|max:50|unique:employees,nik,' . $this->employeeId
            : 'required|string|max:50|unique:employees,nik';

        return [
            'nik'        => $nikRule,
            'nama'       => 'required|string|max:150',
            'jabatan_id' => 'required|exists:jabatan,id',
            'join_date'  => 'required|date',
            'status'     => 'required|in:active,inactive',
            'no_hp'      => 'nullable|string|max:20',
            'alamat'     => 'nullable|string',
        ];
    }

    public function updatingSearch(): void  { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    // --- Access Control ---
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
        $this->reset(['employeeId', 'nik', 'nama', 'jabatan_id', 'join_date', 'status', 'employment_type', 'no_hp', 'alamat', 'user_id']);
        $this->status = 'active';
        $this->join_date = now()->format('Y-m-d');
        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canEdit(), 403);
        $emp = Employee::findOrFail($id);
        $this->employeeId = $emp->id;
        $this->nik        = $emp->nik;
        $this->nama       = $emp->nama;
        $this->jabatan_id = $emp->jabatan_id;
        $this->join_date  = $emp->join_date->format('Y-m-d');
        $this->status          = $emp->status;
        $this->employment_type = $emp->employment_type;
        $this->no_hp           = $emp->no_hp;
        $this->alamat     = $emp->alamat;
        $this->user_id    = $emp->user_id;
        $this->isEditing  = true;
        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'nik'        => $this->nik,
            'nama'       => $this->nama,
            'jabatan_id' => $this->jabatan_id,
            'join_date'  => $this->join_date,
            'status'          => $this->status,
            'employment_type' => $this->employment_type,
            'no_hp'           => $this->no_hp,
            'alamat'     => $this->alamat,
            'user_id'    => $this->user_id ?: null,
        ];

        if ($this->isEditing) {
            abort_unless($this->canEdit(), 403);
            Employee::findOrFail($this->employeeId)->update($data);
            session()->flash('success', 'Data karyawan berhasil diperbarui.');
        } else {
            abort_unless($this->canCreate(), 403);
            Employee::create($data);
            session()->flash('success', 'Karyawan berhasil ditambahkan.');
        }

        $this->isModalOpen = false;
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
        Employee::findOrFail($this->deleteId)->delete();
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
        session()->flash('success', 'Karyawan berhasil dihapus.');
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->showDeleteConfirm = false;
    }

    public function render()
    {
        $employees = Employee::with('jabatan')
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('nama', 'like', "%{$this->search}%")
                  ->orWhere('nik', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('nama')
            ->paginate($this->perPage);

        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();

        return view('livewire.admin.hrd.employee-management', [
            'employees'   => $employees,
            'jabatanList' => $jabatanList,
        ])->layout('layouts.admin');
    }
}
