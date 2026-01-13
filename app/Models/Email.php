<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $table = 'emails';

    protected $fillable = [
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
    ];

    protected $casts = [
        'date' => 'datetime',
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
}
