<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Agar tanggal bisa diformat ($j->transaction_date->format(...))
    protected $casts = [
        'transaction_date' => 'date',
    ];

    // Relasi ke Anak-anaknya (Debit/Kredit items)
    public function items()
    {
        return $this->hasMany(JournalItem::class);
    }

    // Relasi ke User pembuat
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke Cash Transactions
    public function cashTransactions()
    {
        return $this->hasMany(\App\Models\CashTransaction::class, 'journal_id');
    }

    // Relasi ke Bank Transaction
    public function bankTransaction()
    {
        return $this->hasOne(\App\Models\BankTransaction::class, 'journal_id');
    }
}