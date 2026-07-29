<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $table = 'emails';

    /**
     * CATATAN: daftar di bawah memuat DUA generasi nama kolom.
     *
     * Nama sebenarnya di tabel adalah `mailbox`, `body`, dan `email_date`;
     * entri `mailbox_id`, `body_text`, `body_html`, `date`, `to_email`, `cc`,
     * `bcc`, `is_starred`, `is_archived`, dan `folder` adalah sisa rancangan
     * lama yang kolomnya tidak pernah ada. Sisa itu sengaja dibiarkan (tidak
     * berbahaya, dan menghapusnya di luar lingkup), tapi nama yang benar
     * WAJIB ada di sini — tanpa itu, `Email::create()` membuang field
     * pentingnya diam-diam.
     *
     * Kode sinkronisasi inbox sendiri memakai query builder langsung
     * (`DB::table('emails')`), jadi drift ini tidak pernah menggigit
     * production.
     */
    protected $fillable = [
        'mailbox',
        'body',
        'email_date',
        'mailbox_id',
        'message_id',
        'subject',
        'from_email',
        'from_name',
        'to_email',
        'cc',
        'bcc',
        'body_text',
        'body_html',
        'date',
        'is_read',
        'is_starred',
        'is_archived',
        'folder',
        'uid',
        'replied_at',
        'replied_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'email_date' => 'datetime',
        'replied_at' => 'datetime',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'is_archived' => 'boolean',
        'cc' => 'array',
        'bcc' => 'array',
    ];

    public function mailbox()
    {
        return $this->belongsTo(EmailMailbox::class, 'mailbox_id');
    }

    public function attachments()
    {
        return $this->hasMany(EmailAttachment::class, 'email_id');
    }

    /**
     * Email masuk yang belum dibalas staf.
     */
    public function scopeBelumDibalas($query)
    {
        return $query->whereNull('replied_at');
    }

    /**
     * Belum dibalas melewati batas wajar — kandidat paling mungkin
     * berubah jadi keluhan customer.
     */
    public function scopeMenggantung($query, int $jam = 24)
    {
        return $query->whereNull('replied_at')
            ->where('email_date', '<', now()->subHours($jam));
    }
}
