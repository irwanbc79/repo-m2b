<?php

namespace App\Services;

use App\Models\InternalMessage;
use App\Models\InternalMessageRead;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aturan main chat internal M2B.
 *
 * Kelayakan peserta ditentukan di SINI supaya hanya ada satu sumber kebenaran
 * — komponen tampilan tidak boleh punya versi aturannya sendiri.
 */
class InternalChatService
{
    /**
     * Role yang TIDAK ikut chat internal.
     *
     * ⚠️ Wajib diperiksa terhadap kolom `roles` (JSON), BUKAN kolom `role`.
     * Konsultan Pajak di portal ini tercatat `role = 'staff'` dan hanya
     * dikenali lewat `roles = ["konsultan_pajak"]`. Menyaring pakai kolom
     * `role` akan diam-diam MEMASUKKAN dia ke obrolan tim.
     */
    public const ROLE_DIKECUALIKAN = ['auditor', 'konsultan_pajak'];

    /** Berapa lama pesan disimpan sebelum dibersihkan otomatis. */
    public const SIMPAN_HARI = 90;

    /**
     * Semua orang yang boleh memakai chat internal.
     *
     * Disaring di PHP, bukan lewat query JSON MySQL: jumlah pengguna internal
     * hanya belasan sehingga bedanya nol, sementara fungsi JSON berperilaku
     * berbeda antara MySQL (production) dan SQLite (test) — portal ini sudah
     * punya satu halaman yang rusak persis karena hal itu.
     */
    public function pesertaLayak(): Collection
    {
        return User::query()
            ->where('role', '!=', 'customer')
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'roles'])
            ->reject(fn (User $u) => $this->dikecualikan($u))
            ->values();
    }

    public function boleh(?User $user): bool
    {
        if (! $user || $user->role === 'customer') {
            return false;
        }

        return ! $this->dikecualikan($user);
    }

    private function dikecualikan(User $user): bool
    {
        foreach ($this->rolesOf($user) as $r) {
            if (in_array($r, self::ROLE_DIKECUALIKAN, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gabungan kolom `role` dan `roles` JSON — keduanya dipakai di portal ini
     * dan bisa saling bertentangan, jadi keduanya diperiksa.
     */
    private function rolesOf(User $user): array
    {
        $daftar = [];

        if (! empty($user->role)) {
            $daftar[] = $user->role;
        }

        $raw = $user->roles;
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true);
        }
        if (is_array($raw)) {
            $daftar = array_merge($daftar, $raw);
        }

        return array_map(fn ($r) => is_string($r) ? trim($r) : $r, $daftar);
    }

    /** Lawan bicara yang tersedia untuk japri (semua peserta kecuali diri sendiri). */
    public function lawanBicara(User $me): Collection
    {
        return $this->pesertaLayak()->reject(fn ($u) => (int) $u->id === (int) $me->id)->values();
    }

    /**
     * @param array{path:string,name:string,mime:string,size:int}|null $lampiran
     */
    public function kirim(User $pengirim, string $body, ?int $recipientId = null, ?array $lampiran = null, ?int $replyToId = null): InternalMessage
    {
        $body = trim($body);

        // Pesan boleh kosong ASAL ada lampiran — mengirim gambar tanpa
        // keterangan itu wajar.
        if ($body === '' && ! $lampiran) {
            throw new \InvalidArgumentException('Pesan tidak boleh kosong.');
        }
        if (! $this->boleh($pengirim)) {
            throw new \RuntimeException('Anda tidak memiliki akses ke chat internal.');
        }

        if ($recipientId) {
            // Japri hanya boleh ke sesama peserta yang layak — mencegah pesan
            // terkirim ke auditor/konsultan pajak lewat id yang dikarang.
            $sah = $this->lawanBicara($pengirim)->contains(fn ($u) => (int) $u->id === (int) $recipientId);
            if (! $sah) {
                throw new \RuntimeException('Penerima tidak termasuk peserta chat internal.');
            }
        }

        $replyTo = $replyToId ? InternalMessage::find($replyToId) : null;
        $replyToSender = $replyTo?->sender_name;
        $replyToBody = $replyTo ? $replyTo->ringkasanBalasan() : null;

        return InternalMessage::create([
            'conversation_key' => $recipientId
                ? InternalMessage::dmKey((int) $pengirim->id, (int) $recipientId)
                : InternalMessage::KEY_ALL,
            'scope'        => $recipientId ? InternalMessage::SCOPE_DM : InternalMessage::SCOPE_ALL,
            'sender_id'    => $pengirim->id,
            'sender_name'  => $pengirim->name,
            'recipient_id' => $recipientId,
            'body'         => mb_substr($body, 0, 2000),

            'reply_to_id'     => $replyTo?->id,
            'reply_to_sender' => $replyToSender,
            'reply_to_body'   => $replyToBody,

            'attachment_path' => $lampiran['path'] ?? null,
            'attachment_name' => $lampiran['name'] ?? null,
            'attachment_mime' => $lampiran['mime'] ?? null,
            'attachment_size' => $lampiran['size'] ?? null,
        ]);
    }

    /**
     * Bolehkah user ini membuka lampiran pesan tertentu?
     *
     * Diperiksa di sini, bukan di controller, supaya aturannya sama dengan
     * aturan siapa boleh MELIHAT pesannya. Japri hanya boleh dibuka oleh
     * pengirim & penerimanya.
     */
    public function bolehLihatLampiran(User $me, InternalMessage $m): bool
    {
        if (! $this->boleh($me) || ! $m->punyaLampiran()) {
            return false;
        }

        if ($m->scope === InternalMessage::SCOPE_DM) {
            return (int) $m->sender_id === (int) $me->id
                || (int) $m->recipient_id === (int) $me->id;
        }

        return true;
    }

    /**
     * Ambil pesan satu percakapan.
     *
     * Japri disaring di lapisan QUERY, bukan di tampilan: mode gagal terburuk
     * fitur ini adalah pesan pribadi terbaca orang lain, dan penyaringan di
     * blade terlalu mudah terlewat saat kode berubah.
     */
    public function pesan(User $me, ?int $lawanId = null, int $batas = 50): Collection
    {
        $key = $lawanId
            ? InternalMessage::dmKey((int) $me->id, (int) $lawanId)
            : InternalMessage::KEY_ALL;

        $q = InternalMessage::where('conversation_key', $key);

        if ($lawanId) {
            $q->where(function ($w) use ($me) {
                $w->where('sender_id', $me->id)->orWhere('recipient_id', $me->id);
            });
        }

        return $q->orderByDesc('id')->limit($batas)->get()->reverse()->values();
    }

    /** Jumlah pesan belum dibaca di satu percakapan. */
    public function belumDibaca(User $me, ?int $lawanId = null): int
    {
        $key = $lawanId
            ? InternalMessage::dmKey((int) $me->id, (int) $lawanId)
            : InternalMessage::KEY_ALL;

        $batas = InternalMessageRead::where('user_id', $me->id)
            ->where('conversation_key', $key)->value('last_read_message_id') ?? 0;

        return InternalMessage::where('conversation_key', $key)
            ->where('id', '>', $batas)
            // Pesan sendiri tidak pernah dihitung belum dibaca.
            ->where('sender_id', '!=', $me->id)
            ->count();
    }

    /**
     * Total belum dibaca lintas semua percakapan — untuk lencana di tombol.
     *
     * Sengaja SATU query agregat, bukan perulangan per lawan bicara: ini
     * dipanggil pada setiap polling, jadi biayanya harus tetap datar berapa
     * pun jumlah pesertanya.
     */
    public function totalBelumDibaca(User $me): int
    {
        $batasPerKunci = InternalMessageRead::where('user_id', $me->id)
            ->pluck('last_read_message_id', 'conversation_key');

        $q = InternalMessage::where('sender_id', '!=', $me->id)
            ->where(function ($w) use ($me) {
                $w->where('conversation_key', InternalMessage::KEY_ALL)
                  ->orWhere('recipient_id', $me->id);
            });

        return $q->get(['id', 'conversation_key'])
            ->filter(fn ($m) => $m->id > (int) ($batasPerKunci[$m->conversation_key] ?? 0))
            ->count();
    }

    public function tandaiTerbaca(User $me, ?int $lawanId = null): void
    {
        $key = $lawanId
            ? InternalMessage::dmKey((int) $me->id, (int) $lawanId)
            : InternalMessage::KEY_ALL;

        $terakhir = (int) InternalMessage::where('conversation_key', $key)->max('id');

        InternalMessageRead::updateOrCreate(
            ['user_id' => $me->id, 'conversation_key' => $key],
            ['last_read_message_id' => $terakhir]
        );
    }

    /**
     * Penanda perubahan yang sangat murah untuk polling.
     *
     * Yang dikirim balik hanya "id pesan terbaru yang relevan bagi saya".
     * Selama angkanya tidak berubah, panel tidak perlu mengambil apa pun.
     */
    public function penandaTerbaru(User $me): int
    {
        return (int) InternalMessage::where(function ($w) use ($me) {
            $w->where('conversation_key', InternalMessage::KEY_ALL)
              ->orWhere('recipient_id', $me->id)
              ->orWhere('sender_id', $me->id);
        })->max('id');
    }
}
