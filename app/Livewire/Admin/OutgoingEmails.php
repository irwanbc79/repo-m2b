<?php

namespace App\Livewire\Admin;

use App\Models\EmailDelivery;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Layar "Keluar" — semua email yang dikirim portal beserta nasibnya.
 *
 * Menutup titik buta yang selama ini paling mahal: email otomatis (invoice,
 * quotation, update shipment) dikirim dari ~20 tempat lalu hilang jejak. Staf
 * penagihan jadi mengejar piutang tanpa tahu tagihannya sampai atau tidak.
 *
 * Sumber datanya `email_deliveries` yang diisi listener global, disilangkan
 * dengan riwayat peristiwa dari Kirim Email.
 */
class OutgoingEmails extends Component
{
    use WithPagination;

    public string $search = '';

    /** all|invoice|quotation|shipment|sistem */
    public string $jenis = 'all';

    /** all|bounced|unopened|opened|stuck */
    public string $kondisi = 'all';

    protected $queryString = [
        'search'  => ['except' => ''],
        'jenis'   => ['except' => 'all'],
        'kondisi' => ['except' => 'all'],
    ];

    public function updating($key): void
    {
        // Ganti penyaring = kembali ke halaman satu, supaya tidak mendarat di
        // halaman kosong.
        if (in_array($key, ['search', 'jenis', 'kondisi'], true)) {
            $this->resetPage();
        }
    }

    public function setJenis(string $jenis): void
    {
        $this->jenis = $jenis;
    }

    public function setKondisi(string $kondisi): void
    {
        $this->kondisi = $kondisi;
    }

    public function render()
    {
        $items = EmailDelivery::query()
            ->with('related')
            ->when($this->search, function ($q) {
                $q->where(function ($w) {
                    $w->where('recipient_email', 'like', "%{$this->search}%")
                        ->orWhere('subject', 'like', "%{$this->search}%");
                });
            })
            ->when($this->jenis !== 'all', fn ($q) => $this->terapkanJenis($q))
            ->when($this->kondisi !== 'all', fn ($q) => $this->terapkanKondisi($q))
            ->latest('sent_at')
            ->paginate(25);

        // Layout admin harus disebut eksplisit — default Livewire di project
        // ini (components.layouts.app) tidak memuat sidebar admin.
        return view('livewire.admin.outgoing-emails', [
            'items'   => $items,
            'ringkas' => $this->ringkasan(),
        ])->layout('layouts.admin');
    }

    private function terapkanJenis($query)
    {
        return match ($this->jenis) {
            'invoice'   => $query->where('related_type', \App\Models\Invoice::class),
            'quotation' => $query->where('related_type', \App\Models\Quotation::class),
            'shipment'  => $query->where('related_type', \App\Models\Shipment::class),
            // "Sistem" = email yang tidak tertaut entitas mana pun: briefing
            // harian, peringatan pembukuan, dan sejenisnya.
            'sistem'    => $query->whereNull('related_type'),
            default     => $query,
        };
    }

    private function terapkanKondisi($query)
    {
        return match ($this->kondisi) {
            'bounced'  => $query->whereIn('status', [EmailDelivery::STATUS_BOUNCED, EmailDelivery::STATUS_FAILED]),
            'opened'   => $query->where('open_count', '>', 0),
            // Sudah sampai tapi belum dibuka — kandidat untuk disusul lewat
            // telepon atau WhatsApp.
            'unopened' => $query->where('status', EmailDelivery::STATUS_DELIVERED)->where('open_count', 0),
            'stuck'    => $query->stuck(60),
            default    => $query,
        };
    }

    /**
     * Angka ringkas 30 hari terakhir untuk kepala halaman.
     */
    private function ringkasan(): array
    {
        $sejak = now()->subDays(30);
        $dasar = fn () => EmailDelivery::where('sent_at', '>=', $sejak);

        $total   = $dasar()->count();
        $gagal   = $dasar()->whereIn('status', [EmailDelivery::STATUS_BOUNCED, EmailDelivery::STATUS_FAILED])->count();
        $dibuka  = $dasar()->where('open_count', '>', 0)->count();
        $sampai  = $dasar()->whereIn('status', [
            EmailDelivery::STATUS_DELIVERED,
            EmailDelivery::STATUS_OPENED,
            EmailDelivery::STATUS_CLICKED,
        ])->count();

        return [
            'total'        => $total,
            'gagal'        => $gagal,
            'mangkrak'     => EmailDelivery::stuck(60)->count(),
            // Persentase hanya bermakna bila sudah ada yang terkirim.
            'rasio_sampai' => $total > 0 ? round($sampai / $total * 100, 1) : null,
            'rasio_dibuka' => $sampai > 0 ? round($dibuka / $sampai * 100, 1) : null,
        ];
    }
}
