<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu pesan chat internal. Lihat migration untuk arti conversation_key.
 */
class InternalMessage extends Model
{
    public const SCOPE_ALL = 'all';
    public const SCOPE_DM  = 'dm';

    public const KEY_ALL = 'all';

    protected $fillable = [
        'conversation_key', 'scope', 'sender_id', 'sender_name',
        'recipient_id', 'body', 'is_pinned',
        'reply_to_id', 'reply_to_sender', 'reply_to_body',
        'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size',
    ];

    protected $casts = [
        'is_pinned'       => 'boolean',
        'attachment_size' => 'integer',
        'reply_to_id'     => 'integer',
    ];

    public function replyTo()
    {
        return $this->belongsTo(InternalMessage::class, 'reply_to_id');
    }

    public function isReply(): bool
    {
        return ! empty($this->reply_to_sender) || ! empty($this->reply_to_id);
    }

    /** Ringkasan teks untuk kutipan balasan */
    public function ringkasanBalasan(): string
    {
        if (filled($this->body)) {
            return mb_substr($this->body, 0, 100);
        }

        if ($this->punyaLampiran()) {
            return '📎 ' . ($this->attachment_name ?: 'Lampiran');
        }

        return 'Pesan';
    }

    public function punyaLampiran(): bool
    {
        return ! empty($this->attachment_path);
    }

    public function lampiranGambar(): bool
    {
        return $this->punyaLampiran() && str_starts_with((string) $this->attachment_mime, 'image/');
    }

    public function ikonLampiran(): string
    {
        $ext = strtolower(pathinfo($this->attachment_name, PATHINFO_EXTENSION));
        $mime = strtolower((string) $this->attachment_mime);

        if (in_array($ext, ['xlsx', 'xls', 'csv', 'tsv', 'ods'], true) || str_contains($mime, 'spreadsheet') || str_contains($mime, 'csv') || str_contains($mime, 'excel')) {
            return '📊';
        }
        if (in_array($ext, ['docx', 'doc', 'odt'], true) || str_contains($mime, 'word') || str_contains($mime, 'document')) {
            return '📝';
        }
        if (in_array($ext, ['pptx', 'ppt', 'odp'], true) || str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint')) {
            return '📽️';
        }
        if ($ext === 'pdf' || $mime === 'application/pdf') {
            return '📄';
        }

        return '📎';
    }

    public function ekstensiLampiran(): string
    {
        return strtoupper(pathinfo($this->attachment_name, PATHINFO_EXTENSION) ?: 'FILE');
    }

    /** Ukuran dalam bentuk yang enak dibaca staf. */
    public function ukuranTerbaca(): string
    {
        $b = (int) $this->attachment_size;

        return $b >= 1048576
            ? round($b / 1048576, 1) . ' MB'
            : max(1, (int) round($b / 1024)) . ' KB';
    }

    /**
     * Kunci japri yang sudah dinormalkan: id kecil dulu, supaya pesan A→B dan
     * B→A jatuh di percakapan yang sama.
     */
    public static function dmKey(int $a, int $b): string
    {
        return 'dm:' . min($a, $b) . '-' . max($a, $b);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
