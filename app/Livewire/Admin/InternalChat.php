<?php

namespace App\Livewire\Admin;

use App\Services\InternalChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

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
    use WithFileUploads;

    /** Jenis file yang diterima. Menolak sisanya bukan cuma soal disk —
     *  mencegah file berbahaya diunggah lalu terunduh orang lain. */
    private const MIME_DIIZINKAN = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf',
    ];

    private const MAKS_GAMBAR_KB = 5120;   // 5 MB
    private const MAKS_PDF_KB    = 10240;  // 10 MB

    public bool $terbuka = false;

    /** File yang sedang dipilih, belum terkirim. */
    public $berkas = null;

    /** null = obrolan "Semua"; berisi id user = japri. */
    public ?int $lawan = null;

    public string $isi = '';

    /** Penanda pesan terbaru yang sudah terlihat komponen ini. */
    public int $penanda = 0;

    /** Jumlah belum-dibaca pada denyut sebelumnya — dasar penentu bunyi ping. */
    public int $belumTerakhir = 0;

    private function svc(): InternalChatService
    {
        return app(InternalChatService::class);
    }

    /**
     * Ambil keadaan awal supaya denyut PERTAMA setelah halaman dimuat tidak
     * dianggap "ada yang baru". Tanpa ini, $penanda mulai dari 0 sehingga
     * setiap kali staf berpindah halaman admin, ping akan berbunyi untuk
     * pesan lama yang sudah lama ada.
     */
    public function mount(): void
    {
        $me = Auth::user();
        if ($me && $this->svc()->boleh($me)) {
            $this->penanda       = $this->svc()->penandaTerbaru($me);
            $this->belumTerakhir = $this->svc()->totalBelumDibaca($me);
        }
    }

    public function toggle(): void
    {
        $this->terbuka = ! $this->terbuka;

        if ($this->terbuka) {
            $this->tandaiTerbaca();
            $this->dispatch('scroll-chat-bawah');
        }
    }

    public function pilihLawan(?int $id = null): void
    {
        $this->lawan = $id ?: null;
        $this->tandaiTerbaca();
        $this->dispatch('scroll-chat-bawah');
    }

    public function kirim(): void
    {
        $me = Auth::user();

        try {
            $lampiran = $this->berkas ? $this->simpanBerkas() : null;

            $this->svc()->kirim($me, $this->isi, $this->lawan, $lampiran);

            $this->isi = '';
            $this->berkas = null;
            $this->tandaiTerbaca();
            $this->dispatch('scroll-chat-bawah');
        } catch (\Throwable $e) {
            $this->addError('isi', $e->getMessage());
        }
    }

    public function batalBerkas(): void
    {
        $this->berkas = null;
    }

    /**
     * Simpan lampiran ke disk PRIVAT dan kembalikan metadatanya.
     *
     * Disk `local`, bukan `public`: file di `public` bisa dibuka siapa saja
     * yang tahu URL-nya tanpa login. Untuk lampiran chat internal itu tidak
     * boleh — file hanya dilayani lewat controller yang memeriksa hak akses.
     */
    private function simpanBerkas(): array
    {
        $mime = $this->berkas->getMimeType();
        $kb   = (int) ceil($this->berkas->getSize() / 1024);

        if (! in_array($mime, self::MIME_DIIZINKAN, true)) {
            throw new \RuntimeException('Hanya gambar (JPG/PNG/WEBP/GIF) dan PDF yang bisa dilampirkan.');
        }

        $gambar = str_starts_with($mime, 'image/');
        $maks   = $gambar ? self::MAKS_GAMBAR_KB : self::MAKS_PDF_KB;

        if ($kb > $maks) {
            throw new \RuntimeException(
                'Ukuran file ' . round($kb / 1024, 1) . ' MB melebihi batas '
                . round($maks / 1024) . ' MB.'
            );
        }

        $nama = $this->berkas->getClientOriginalName();
        $ext  = strtolower($this->berkas->getClientOriginalExtension() ?: 'bin');

        // Gambar diperkecil dulu — pola yang sama dipakai bukti kas kecil,
        // supaya foto dari HP tidak menghabiskan disk.
        if ($gambar && in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $img = $manager->read($this->berkas->getRealPath());
            if ($img->width() > 1600) {
                $img->scale(width: 1600);
            }

            $path = 'chat-internal/' . uniqid() . '.jpg';
            Storage::disk('local')->put($path, $img->toJpeg(80));

            return [
                'path' => $path,
                'name' => $nama,
                'mime' => 'image/jpeg',
                'size' => Storage::disk('local')->size($path),
            ];
        }

        $path = $this->berkas->store('chat-internal', 'local');

        return [
            'path' => $path,
            'name' => $nama,
            'mime' => $mime,
            'size' => $this->berkas->getSize(),
        ];
    }

    /**
     * Denyut polling. Sengaja tidak melakukan apa pun bila tidak ada pesan
     * baru — inilah yang membuat polling tiap 10 detik tetap murah.
     */
    public function denyut(): void
    {
        $me  = Auth::user();
        $svc = $this->svc();

        // Kelayakan diperiksa DI SINI, bukan hanya di render(). Tanpa ini,
        // auditor & konsultan pajak tetap menerima peristiwa `chat-masuk`
        // dan mendengar ping tiap kali tim internal mengobrol — panel mereka
        // memang kosong, tapi bunyinya membocorkan bahwa ada percakapan.
        if (! $me || ! $svc->boleh($me)) {
            return;
        }

        $terbaru = $svc->penandaTerbaru($me);

        // Jalur murah: tidak ada yang baru sama sekali, berhenti di sini.
        if ($terbaru === $this->penanda) {
            return;
        }

        $this->penanda = $terbaru;

        // Bunyi dipicu oleh JUMLAH BELUM DIBACA, bukan oleh $penanda.
        // $penanda ikut berubah saat kita sendiri yang mengirim pesan, jadi
        // memakainya akan membunyikan ping ke diri sendiri setiap kali kirim.
        // totalBelumDibaca() sudah mengecualikan sender_id = kita.
        $belum = $svc->totalBelumDibaca($me);

        if ($belum > $this->belumTerakhir) {
            $this->dispatch('chat-masuk');
        }

        $this->belumTerakhir = $belum;

        if ($this->terbuka) {
            $this->tandaiTerbaca();
            $this->dispatch('scroll-chat-bawah');
        }
    }

    private function tandaiTerbaca(): void
    {
        $me = Auth::user();
        if (! $me || ! $this->svc()->boleh($me)) {
            return;
        }

        $this->svc()->tandaiTerbaca($me, $this->lawan);

        // Segarkan dasar perbandingan bunyi DI SINI, bukan di pemanggilnya.
        // Menandai terbaca menurunkan jumlah belum-dibaca; kalau
        // $belumTerakhir dibiarkan tinggi, pesan berikutnya tidak akan pernah
        // melampauinya dan pingnya ditelan diam-diam.
        //
        // Dihitung ulang, BUKAN disetel 0: tandaiTerbaca() hanya melunasi
        // obrolan yang sedang dibuka, sisa dari obrolan lain masih berlaku.
        $this->belumTerakhir = $this->svc()->totalBelumDibaca($me);
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
