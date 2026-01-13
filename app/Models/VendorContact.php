<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model untuk menyimpan banyak kontak (PIC) untuk satu Vendor.
 * Field 'vendor_id' adalah kunci relasi.
 */
class VendorContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'pic_name',
        'phone',
        'email',
        'role',
        'is_primary', // Untuk menandai kontak utama
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}