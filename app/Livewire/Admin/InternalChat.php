<?php

namespace App\Livewire\Admin;

use App\Services\InternalChatService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Chat internal — tombol mengambang di sudut kanan bawah, tampil di seluruh
 * halaman admin.
 *
 * Karena komponen ini ikut di SETIAP halaman, biayanya dijaga ketat:
 *
 * 1. Saat panel TERTUTUP, render() hanya menghitung jumlah belum dibaca.
 *    Daftar peserta & isi pesan tidak disentuh sama sekali.
 * 2. Denyut polling melambat saat tertutup (60 detik) dan mengencang saat
 *    dibuka (10 detik).
 * 3. Denyut membandingkan satu angka penanda dulu; isi pesan diambil ulang
 *    hanya kalau angkanya berubah.
 *
 * Polling saat tab tidak aktif tidak perlu diurus — Livewire 3 sudah
 * menghentikannya sendiri (terverifikasi di sumber: visibilitychange →
 * theTabIsInTheBackground).
 */
class InternalChat extends Component
{
    public bool $terbuka = false;

    /** null = obrolan "Semua"; berisi id user = japri. */
    public ?int $lawan = null;

    public string $isi = '';

    /** Penanda pesan terbaru yang sudah terlihat komponen ini. */
    public int $penanda = 0;

    private function svc(): InternalChatService
    {
        return app(InternalChatService::class);
    }

    public function toggle(): void
    {
        $this->terbuka = ! $this->terbuka;

        if ($this->terbuka) {
            $this->tandaiTerbaca();
        }
    }

    public function pilihLawan(?int $id = null): void
    {
        $this->lawan = $id ?: null;
        $this->tandaiTerbaca();
    }

    public function kirim(): void
    {
        $me = Auth::user();

        try {
            $this->svc()->kirim($me, $this->isi, $this->lawan);
            $this->isi = '';
            $this->tandaiTerbaca();
        } catch (\Throwable $e) {
            $this->addError('isi', $e->getMessage());
        }
    }

    /**
     * Denyut polling. Sengaja tidak melakukan apa pun bila tidak ada pesan
     * baru — inilah yang membuat polling tiap 10 detik tetap murah.
     */
    public function denyut(): void
    {
        $me = Auth::user();
        if (! $me) {
            return;
        }

        $terbaru = $this->svc()->penandaTerbaru($me);

        if ($terbaru !== $this->penanda) {
            $this->penanda = $terbaru;

            if ($this->terbuka) {
                $this->tandaiTerbaca();
            }
        }
    }

    private function tandaiTerbaca(): void
    {
        $me = Auth::user();
        if ($me && $this->svc()->boleh($me)) {
            $this->svc()->tandaiTerbaca($me, $this->lawan);
        }
    }

    public function render()
    {
        $me  = Auth::user();
        $svc = $this->svc();

        // Yang dikecualikan (auditor, konsultan pajak) & customer tidak
        // melihat apa pun — komponen merender kosong.
        if (! $svc->boleh($me)) {
            return view('livewire.admin.internal-chat', [
                'aktif' => false, 'total' => 0, 'peserta' => collect(), 'pesan' => collect(),
            ]);
        }

        $total = $svc->totalBelumDibaca($me);

        // Saat tertutup: berhenti di sini. Tidak ada query peserta/pesan.
        if (! $this->terbuka) {
            return view('livewire.admin.internal-chat', [
                'aktif' => true, 'total' => $total, 'peserta' => collect(), 'pesan' => collect(),
            ]);
        }

        $peserta = $svc->lawanBicara($me)->map(fn ($u) => [
            'id'     => $u->id,
            'name'   => $u->name,
            'unread' => $svc->belumDibaca($me, $u->id),
        ]);

        return view('livewire.admin.internal-chat', [
            'aktif'   => true,
            'total'   => $total,
            'peserta' => $peserta,
            'pesan'   => $svc->pesan($me, $this->lawan),
        ]);
    }
}
