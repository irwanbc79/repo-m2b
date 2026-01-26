<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    // Guarded kosong = Semua kolom boleh diisi (Aman untuk Mass Assignment)
    protected $guarded = []; 

    // Relasi ke User (Login) - LEGACY: untuk backward compatibility
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Multiple Users (Many-to-Many via pivot table)
    public function users()
    {
        return $this->belongsToMany(User::class, 'customer_user')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    // Get Primary User (PIC Utama)
    public function primaryUser()
    {
        return $this->users()->wherePivot('is_primary', true)->first();
    }

    // Get All Secondary Users (PIC Tambahan)
    public function secondaryUsers()
    {
        return $this->users()->wherePivot('is_primary', false);
    }

    // Relasi ke Shipments (Pengiriman)
    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    // Relasi ke Invoices (Tagihan)
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Generate unique customer code: CUST-XXXXXX
     */
    public static function generateCustomerCode()
    {
        $lastCustomer = self::orderBy('id', 'desc')->first();
        
        if (!$lastCustomer) {
            return 'CUST-000001';
        }
        
        $lastCode = $lastCustomer->customer_code;
        
        if (preg_match('/CUST-(\d{6})$/', $lastCode, $matches)) {
            $lastNumber = intval($matches[1]);
        } else {
            $lastNumber = self::max('id');
        }
        
        $newNumber = $lastNumber + 1;
        return 'CUST-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}
