<?php

namespace App\Livewire\Admin;

use App\Models\DocumentType;
use App\Models\LartasReference;
use App\Models\Shipment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kelola Referensi Lartas (basis pengetahuan INSW M2B).
 * Lihat/tambah/edit/hapus + daftar HS yang sering muncul tapi belum direkam.
 */
class LartasReferences extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $onlyStale = false;

    // Form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $f_hs_code = '';
    public string $f_trade_flow = 'import';
    public bool $f_is_free = false;
    public string $f_izin_names = '';
    public string $f_izin_code = '';
    public string $f_komoditi_group = '';
    public string $f_regulation = '';
    public string $f_description = '';
    public array $f_doc_types = [];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'f_hs_code'     => 'required|string|max:30',
            'f_trade_flow'  => 'required|in:import,export',
            'f_izin_names'  => 'nullable|string|max:255',
            'f_izin_code'   => 'nullable|string|max:60',
            'f_regulation'  => 'nullable|string|max:255',
            'f_doc_types'   => 'array',
        ];
    }

    public function newReference(?string $hs = null, string $flow = 'import')
    {
        $this->reset(['editingId', 'f_is_free', 'f_izin_names', 'f_izin_code', 'f_komoditi_group', 'f_regulation', 'f_description', 'f_doc_types']);
        $this->f_hs_code = $hs ?? '';
        $this->f_trade_flow = $flow;
        $this->showForm = true;
    }

    public function edit(int $id)
    {
        $r = LartasReference::findOrFail($id);
        $this->editingId       = $r->id;
        $this->f_hs_code       = $r->hs_code;
        $this->f_trade_flow    = $r->trade_flow;
        $this->f_is_free       = (bool) $r->is_free;
        $this->f_izin_names    = (string) $r->izin_names;
        $this->f_izin_code     = (string) $r->izin_code;
        $this->f_komoditi_group = (string) $r->komoditi_group;
        $this->f_regulation    = (string) $r->regulation;
        $this->f_description   = (string) $r->description;
        $this->f_doc_types     = $r->doc_types ?? [];
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate();

        LartasReference::updateOrCreate(
            ['hs_code' => trim($this->f_hs_code), 'trade_flow' => $this->f_trade_flow],
            [
                'is_free'        => $this->f_is_free,
                'izin_names'     => $this->f_izin_names ?: null,
                'izin_code'      => $this->f_izin_code ?: null,
                'komoditi_group' => $this->f_komoditi_group ?: null,
                'regulation'     => $this->f_regulation ?: null,
                'description'    => $this->f_description ?: null,
                'doc_types'      => array_values(array_filter($this->f_doc_types)),
                'source'         => 'INSW/INTR',
                'checked_by'     => Auth::id(),
                'checked_at'     => now(),
            ]
        );

        $this->showForm = false;
        session()->flash('message', "Referensi lartas HS {$this->f_hs_code} ({$this->f_trade_flow}) tersimpan.");
    }

    public function delete(int $id)
    {
        LartasReference::whereKey($id)->delete();
        session()->flash('message', 'Referensi dihapus.');
    }

    /** Konfirmasi masih berlaku (segarkan tanggal cek tanpa ubah isi). */
    public function refreshChecked(int $id)
    {
        LartasReference::whereKey($id)->update(['checked_by' => Auth::id(), 'checked_at' => now()]);
        session()->flash('message', 'Referensi ditandai sudah dicek ulang hari ini.');
    }

    public function getStaleCountProperty(): int
    {
        return LartasReference::stale()->count();
    }

    /** Katalog dokumen lartas untuk pemetaan. */
    public function getDocOptionsProperty()
    {
        return DocumentType::active()->where('category', 'lartas')
            ->orderBy('sort_order')->pluck('doc_type')->unique()->values();
    }

    /** HS yang sering muncul di shipment tapi BELUM ada referensinya. */
    public function getUnrecordedProperty()
    {
        $existing = LartasReference::select('hs_code', 'trade_flow')->get()
            ->map(fn ($r) => preg_replace('/[^0-9]/', '', $r->hs_code) . '|' . $r->trade_flow)
            ->flip();

        return Shipment::query()
            ->whereNotNull('hs_code')->where('hs_code', '!=', '')
            ->select('hs_code', 'service_type', DB::raw('COUNT(*) as jml'))
            ->groupBy('hs_code', 'service_type')
            ->orderByDesc('jml')
            ->limit(50)->get()
            ->map(function ($row) {
                $flow = in_array(strtolower($row->service_type), ['import', 'export'], true) ? strtolower($row->service_type) : 'import';
                $row->flow = $flow;
                $row->key = preg_replace('/[^0-9]/', '', $row->hs_code) . '|' . $flow;
                return $row;
            })
            ->reject(fn ($row) => $existing->has($row->key))
            ->take(12)
            ->values();
    }

    public function render()
    {
        $refs = LartasReference::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('hs_code', 'like', '%' . $this->search . '%')
                ->orWhere('izin_names', 'like', '%' . $this->search . '%')
                ->orWhere('komoditi_group', 'like', '%' . $this->search . '%')))
            ->when($this->onlyStale, fn ($q) => $q->stale())
            ->orderByRaw('checked_at IS NULL DESC')
            ->orderBy('checked_at')
            ->paginate(20);

        return view('livewire.admin.lartas-references', [
            'refs' => $refs,
        ])->layout('layouts.admin');
    }
}
