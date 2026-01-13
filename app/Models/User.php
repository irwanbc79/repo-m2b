<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\VerifyEmail;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'roles', // <--- Kita pakai 'roles' (jamak)
        'role',  // Tetap simpan ini untuk kompatibilitas kode lama sementara
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array', // <--- PENTING: Agar otomatis jadi Array saat diambil
        ];
    }
    
    public function customer()
{
    return $this->hasOne(\App\Models\Customer::class);
}


    public function sendEmailVerificationNotification()
{
    if (config('mail.disable_verification')) {
        return;
    }

    try {
        $this->notify(new \App\Notifications\VerifyEmail);
    } catch (\Throwable $e) {
        \Log::error('Email verification failed: '.$e->getMessage());
    }
}



    // --- FUNGSI SAKTI: CEK MULTI ROLE ---
    
    // Cara pakai: $user->hasRole(['admin', 'finance'])
    // Artinya: Apakah user ini Admin ATAU Finance? (Salah satu cocok = Boleh)
    public function hasRole($rolesToCheck)
    {
        // 1. Ambil data roles user (Array)
        $userRoles = $this->roles ?? []; 

        // Fallback: Jika kolom roles kosong, ambil dari kolom role lama
        if (empty($userRoles) && $this->role) {
            $userRoles = [$this->role];
        }

        // 2. Jika input cuma string (misal: hasRole('admin')), jadikan array
        if (is_string($rolesToCheck)) {
            $rolesToCheck = [$rolesToCheck];
        }

        // 3. Cek Irisan: Apakah ada role user yang cocok dengan yang diminta?
        // array_intersect akan mencari data yang sama di kedua array
        return count(array_intersect($rolesToCheck, $userRoles)) > 0;
    }
    
    // Helper untuk menampilkan jabatan di tabel (biar cantik)
    public function getRoleDisplayAttribute()
    {
        $roles = $this->roles ?? [$this->role];
        if(empty($roles)) return 'User';
        
        // Ubah ["admin", "staf_accounting"] jadi "Admin, Staf Accounting"
        return implode(', ', array_map(fn($r) => ucwords(str_replace('_', ' ', $r)), $roles));
    }
}