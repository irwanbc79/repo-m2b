<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class QuotationHsLog extends Model
{
    protected $fillable = [
        'quotation_id',
        'quotation_commodity_id',
        'action',
        'user_id',
        'user_name',
        'commodity_lama',
        'commodity_baru',
        'hs_code_lama',
        'hs_code_baru',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Catat satu perubahan. Nama pelaku disalin saat kejadian, bukan diambil
     * lewat relasi saat dibaca — pengguna bisa dihapus atau berganti nama,
     * sedangkan jejak audit harus tetap terbaca bertahun-tahun kemudian.
     */
    public static function catat(
        int $quotationId,
        string $action,
        ?int $commodityId = null,
        ?string $komoditiLama = null,
        ?string $komoditiBaru = null,
        ?string $hsLama = null,
        ?string $hsBaru = null,
    ): void {
        $u = Auth::user();

        static::create([
            'quotation_id'           => $quotationId,
            'quotation_commodity_id' => $commodityId,
            'action'                 => $action,
            'user_id'                => $u?->id,
            'user_name'              => $u?->name,
            'commodity_lama'         => $komoditiLama,
            'commodity_baru'         => $komoditiBaru,
            'hs_code_lama'           => $hsLama,
            'hs_code_baru'           => $hsBaru,
        ]);
    }

    public function ringkas(): string
    {
        return match ($this->action) {
            'ditambah' => "Menambah \"{$this->commodity_baru}\"" . ($this->hs_code_baru ? " (HS {$this->hs_code_baru})" : ''),
            'dihapus'  => "Menghapus \"{$this->commodity_lama}\"" . ($this->hs_code_lama ? " (HS {$this->hs_code_lama})" : ''),
            default    => $this->ringkasPerubahan(),
        };
    }

    private function ringkasPerubahan(): string
    {
        $bagian = [];

        if ($this->commodity_lama !== $this->commodity_baru) {
            $bagian[] = "komoditi \"{$this->commodity_lama}\" → \"{$this->commodity_baru}\"";
        }

        if ($this->hs_code_lama !== $this->hs_code_baru) {
            $bagian[] = 'HS ' . ($this->hs_code_lama ?: '(kosong)') . ' → ' . ($this->hs_code_baru ?: '(kosong)');
        }

        return $bagian ? 'Mengubah ' . implode(', ', $bagian) : 'Mengubah komoditi';
    }
}
