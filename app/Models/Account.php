<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    // Tambahkan 'code' dan kolom lainnya di sini
    protected $fillable = [
        'code',             // <-- INI YANG KURANG TADI
        'name',             // Nama Akun
        'type',             // Tipe Akun (Kas & Bank, Aset, dll)
        'description',      // Deskripsi (Opsional)
        'opening_balance',  // Saldo Awal
        'current_balance',  // Saldo Saat Ini
        'is_active',        // Status Aktif/Nonaktif
    ];

    // Jika bapak menggunakan kolom lain di database, tambahkan juga kesini.
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