<?php

namespace App\Livewire\Admin\HRD;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PayrollPeriod;
use App\Models\PayrollSlip;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class PayrollSlipManagement extends Component
{
    use WithPagination;

    public int $periodId;
    public ?PayrollPeriod $period = null;

    public $search = '';
    public $perPage = 25;

    // Modal state
    public $isModalOpen = false;
    public $isEditing = false;
    public $slipId = null;

    // Form fields
    public $employee_id = '';
    public $gaji_pokok = 0;
    public $tunjangan_transport = 0;
    public $tunjangan_jabatan = 0;
    public $tunjangan_lainnya = 0;
    public $lembur_jam = 0;
    public $lembur_nominal = 0;
    public $potongan_bpjs_kes = 0;
    public $potongan_bpjs_tk = 0;
    public $potongan_lainnya = 0;
    public $catatan = '';

    // Computed total (reactive display only)
    public float $computed_total = 0;

    // Delete confirm
    public $showDeleteConfirm = false;
    public $deleteId = null;

    // Bulk create confirm
    public $showBulkCreateConfirm = false;

    protected $queryString = ['search'];

    protected function rules(): array
    {
        $employeeRule = $this->isEditing
            ? 'required|exists:employees,id'
            : 'required|exists:employees,id|unique:payroll_slips,employee_id,NULL,id,period_id,' . $this->periodId;

        return [
            'employee_id'         => $employeeRule,
            'gaji_pokok'          => 'required|numeric|min:0',
            'tunjangan_transport' => 'required|numeric|min:0',
            'tunjangan_jabatan'   => 'required|numeric|min:0',
            'tunjangan_lainnya'   => 'required|numeric|min:0',
            'lembur_jam'          => 'required|numeric|min:0',
            'lembur_nominal'      => 'required|numeric|min:0',
            'potongan_bpjs_kes'   => 'required|numeric|min:0',
            'potongan_bpjs_tk'    => 'required|numeric|min:0',
            'potongan_lainnya'    => 'required|numeric|min:0',
            'catatan'             => 'nullable|string',
        ];
    }

    public function mount(int $periodId): void
    {
        $this->periodId = $periodId;
        $this->period = PayrollPeriod::findOrFail($periodId);
    }

    public function updatingSearch(): void { $this->resetPage(); }

    // Reactive total calculation
    public function updated($field): void
    {
        $numericFields = [
            'gaji_pokok', 'tunjangan_transport', 'tunjangan_jabatan', 'tunjangan_lainnya',
            'lembur_nominal', 'potongan_bpjs_kes', 'potongan_bpjs_tk', 'potongan_lainnya',
        ];
        if (in_array($field, $numericFields)) {
            $this->recalculateTotal();
        }
    }

    private function recalculateTotal(): void
    {
        $this->computed_total = (float) $this->gaji_pokok
            + (float) $this->tunjangan_transport
            + (float) $this->tunjangan_jabatan
            + (float) $this->tunjangan_lainnya
            + (float) $this->lembur_nominal
            - (float) $this->potongan_bpjs_kes
            - (float) $this->potongan_bpjs_tk
            - (float) $this->potongan_lainnya;
    }

    // --- Access Control ---
    public function canCreate(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'finance'])
            && in_array($this->period?->status, ['draft']);
    }

    public function canEdit(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'finance'])
            && in_array($this->period?->status, ['draft']);
    }

    public function canDelete(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin'])
            && $this->period?->status === 'draft';
    }

    // --- CRUD ---
    public function openCreate(): void
    {
        abort_unless($this->canCreate(), 403);
        $this->reset(['slipId', 'employee_id', 'gaji_pokok', 'tunjangan_transport', 'tunjangan_jabatan',
            'tunjangan_lainnya', 'lembur_jam', 'lembur_nominal', 'potongan_bpjs_kes',
            'potongan_bpjs_tk', 'potongan_lainnya', 'catatan']);
        $this->computed_total = 0;
        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canEdit(), 403);
        $slip = PayrollSlip::findOrFail($id);
        $this->slipId              = $slip->id;
        $this->employee_id         = $slip->employee_id;
        $this->gaji_pokok          = $slip->gaji_pokok;
        $this->tunjangan_transport = $slip->tunjangan_transport;
        $this->tunjangan_jabatan   = $slip->tunjangan_jabatan;
        $this->tunjangan_lainnya   = $slip->tunjangan_lainnya;
        $this->lembur_jam          = $slip->lembur_jam;
        $this->lembur_nominal      = $slip->lembur_nominal;
        $this->potongan_bpjs_kes   = $slip->potongan_bpjs_kes;
        $this->potongan_bpjs_tk    = $slip->potongan_bpjs_tk;
        $this->potongan_lainnya    = $slip->potongan_lainnya;
        $this->catatan             = $slip->catatan;
        $this->recalculateTotal();
        $this->isEditing = true;
        $this->isModalOpen = true;
    }

    public function fillFromJabatan(): void
    {
        if ($this->employee_id) {
            $emp = Employee::with('jabatan')->find($this->employee_id);
            if ($emp && $emp->jabatan) {
                $this->gaji_pokok        = $emp->jabatan->gaji_pokok_default;
                $this->tunjangan_jabatan = $emp->jabatan->tunjangan_jabatan_default;
                $this->recalculateTotal();
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'employee_id'         => $this->employee_id,
            'period_id'           => $this->periodId,
            'gaji_pokok'          => $this->gaji_pokok,
            'tunjangan_transport' => $this->tunjangan_transport,
            'tunjangan_jabatan'   => $this->tunjangan_jabatan,
            'tunjangan_lainnya'   => $this->tunjangan_lainnya,
            'lembur_jam'          => $this->lembur_jam,
            'lembur_nominal'      => $this->lembur_nominal,
            'potongan_bpjs_kes'   => $this->potongan_bpjs_kes,
            'potongan_bpjs_tk'    => $this->potongan_bpjs_tk,
            'potongan_lainnya'    => $this->potongan_lainnya,
            'catatan'             => $this->catatan,
        ];

        if ($this->isEditing) {
            abort_unless($this->canEdit(), 403);
            PayrollSlip::findOrFail($this->slipId)->update($data);
            session()->flash('success', 'Slip gaji berhasil diperbarui.');
        } else {
            abort_unless($this->canCreate(), 403);
            PayrollSlip::create($data);
            session()->flash('success', 'Slip gaji berhasil ditambahkan.');
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
        PayrollSlip::findOrFail($this->deleteId)->delete();
        $this->showDeleteConfirm = false;
        session()->flash('success', 'Slip gaji berhasil dihapus.');
    }

    /**
     * Bulk-create slips for all active employees not yet added to this period.
     */
    public function bulkCreate(): void
    {
        abort_unless($this->canCreate(), 403);

        $existingIds = PayrollSlip::where('period_id', $this->periodId)
            ->pluck('employee_id')
            ->toArray();

        $employees = Employee::with('jabatan')
            ->where('status', 'active')
            ->whereNotIn('id', $existingIds)
            ->get();

        $count = 0;
        foreach ($employees as $emp) {
            PayrollSlip::create([
                'employee_id'        => $emp->id,
                'period_id'          => $this->periodId,
                'gaji_pokok'         => $emp->jabatan->gaji_pokok_default ?? 0,
                'tunjangan_jabatan'  => $emp->jabatan->tunjangan_jabatan_default ?? 0,
                'tunjangan_transport'=> 0,
                'tunjangan_lainnya'  => 0,
                'lembur_jam'         => 0,
                'lembur_nominal'     => 0,
                'potongan_bpjs_kes'  => 0,
                'potongan_bpjs_tk'   => 0,
                'potongan_lainnya'   => 0,
            ]);
            $count++;
        }

        $this->showBulkCreateConfirm = false;
        session()->flash('success', "{$count} slip gaji berhasil dibuat untuk karyawan aktif.");
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->showDeleteConfirm = false;
        $this->showBulkCreateConfirm = false;
    }

    public function render()
    {
        $slips = PayrollSlip::with('employee.jabatan')
            ->where('period_id', $this->periodId)
            ->when($this->search, fn($q) => $q->whereHas('employee', fn($eq) =>
                $eq->where('nama', 'like', "%{$this->search}%")
                   ->orWhere('nik', 'like', "%{$this->search}%")
            ))
            ->orderBy('created_at')
            ->paginate($this->perPage);

        $availableEmployees = Employee::with('jabatan')
            ->where('status', 'active')
            ->when($this->isEditing, fn($q) => $q)
            ->orderBy('nama')
            ->get();

        $totalGajiAll = PayrollSlip::where('period_id', $this->periodId)->sum('total_gaji');

        return view('livewire.admin.hrd.payroll-slip-management', [
            'slips'              => $slips,
            'availableEmployees' => $availableEmployees,
            'totalGajiAll'       => $totalGajiAll,
        ])->layout('layouts.admin');
    }
}
