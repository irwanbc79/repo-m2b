<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',             // Kode Akun
        'name',             // Nama Akun
        'type',             // Tipe Akun (kas_bank, piutang, dll)
        'opening_balance',  // Saldo Awal Master
        'current_balance',  // Saldo Berjalan (disinkronkan dari jurnal)
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ];
    }

    public function journalItems()
    {
        return $this->hasMany(JournalItem::class);
    }

    /**
     * Apakah akun ini bersaldo normal Debit?
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->type, [
            'kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 
            'aset_tetap', 'beban_pokok', 'beban_operasional', 'beban_lain'
        ]);
    }

    /**
     * Hitung saldo aktual langsung dari single source of truth (journal_items).
     */
    public function getCalculatedBalanceAttribute(): float
    {
        if (isset($this->attributes['total_debit']) && isset($this->attributes['total_credit'])) {
            $debit = (float) $this->attributes['total_debit'];
            $credit = (float) $this->attributes['total_credit'];
        } else {
            $debit = (float) $this->journalItems()->sum('debit');
            $credit = (float) $this->journalItems()->sum('credit');
        }

        $opening = (float) ($this->opening_balance ?? 0);

        return (float) ($this->isDebitNormal()
            ? ($opening + ($debit - $credit))
            : ($opening + ($credit - $debit)));
    }

    /**
     * Rekalkulasi dan simpan saldo berjalan ke kolom current_balance.
     */
    public function recalculateBalance(): float
    {
        $debit = (float) $this->journalItems()->sum('debit');
        $credit = (float) $this->journalItems()->sum('credit');
        $opening = (float) ($this->opening_balance ?? 0);

        $balance = $this->isDebitNormal()
            ? ($opening + ($debit - $credit))
            : ($opening + ($credit - $debit));

        $this->updateQuietly(['current_balance' => $balance]);

        return (float) $balance;
    }

    public static function cashOrBank()
    {
        return static::where('type', 'kas_bank')->firstOrFail();
    }

    public static function advanceFromCustomer()
    {
        return static::where('code', '2105')->firstOrFail(); // Uang Muka Pelanggan
    }

    public static function revenueService()
    {
        return static::where('code', '4101')->firstOrFail(); // Pendapatan Jasa Clearance
    }
}