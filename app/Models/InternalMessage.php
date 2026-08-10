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
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

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
