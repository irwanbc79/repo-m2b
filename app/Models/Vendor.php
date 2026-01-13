<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        // 'pic_name', <-- Dihapus karena pindah ke VendorContact
        // 'phone',    <-- Dihapus karena pindah ke VendorContact
        // 'email',    <-- Dihapus karena pindah ke VendorContact
        'address',
        'bank_details',
        'npwp',       // <-- FIX: WAJIB ADA AGAR BISA DISIMPAN
        'website',    // <-- TAMBAHAN: Agar field website juga tersimpan
    ];

    /**
     * Relasi: Satu vendor bisa punya banyak Job Costs.
     */
    public function jobCosts()
    {
        return $this->hasMany(JobCost::class);
    }

    /**
     * Relasi: Satu vendor bisa punya banyak Kontak PIC. (Sesuai usul staf Operasional)
     */
    public function contacts()
    {
        // Menghubungkan ke model VendorContact yang sudah kita buat
        return $this->hasMany(VendorContact::class);
    }
}