<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    protected $fillable = [
        'mailbox',
        'to_email', 
        'subject',
        'body',
        'user_id',
        'user_name'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
