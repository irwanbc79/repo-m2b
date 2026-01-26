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

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $userRoles = $this->roles ?? [$this->role];
        $config = config("permissions.roles");
        
        foreach ($userRoles as $role) {
            if (!isset($config[$role])) continue;
            
            $rolePermissions = $config[$role]["permissions"] ?? [];
            
            // Super admin / director has all permissions
            if (in_array("*", $rolePermissions)) {
                return true;
            }
            
            // Check exact match
            if (in_array($permission, $rolePermissions)) {
                return true;
            }
            
            // Check wildcard (e.g., "invoice.*" matches "invoice.view")
            $permissionGroup = explode(".", $permission)[0];
            if (in_array("{$permissionGroup}.*", $rolePermissions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user can access menu
     */
    public function canAccessMenu(string $menu): bool
    {
        $menuAccess = config("permissions.menu_access.{$menu}", []);
        
        if (empty($menuAccess)) {
            return true; // No restriction defined
        }
        
        foreach ($menuAccess as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get user role level (for hierarchy check)
     */
    public function getRoleLevel(): int
    {
        $userRoles = $this->roles ?? [$this->role];
        $config = config("permissions.roles");
        $maxLevel = 0;
        
        foreach ($userRoles as $role) {
            $level = $config[$role]["level"] ?? 0;
            if ($level > $maxLevel) {
                $maxLevel = $level;
            }
        }
        
        return $maxLevel;
    }

    /**
     * Check if user is admin level or above
     */
    public function isAdminLevel(): bool
    {
        return $this->getRoleLevel() >= 60;
    }

    /**
     * Check if user is manager level or above
     */
    public function isManagerLevel(): bool
    {
        return $this->getRoleLevel() >= 80;
    }

    /**
     * Check if user is director level or above
     */
    public function isDirectorLevel(): bool
    {
        return $this->getRoleLevel() >= 90;
    }

    /**
     * Relasi ke Customer via pivot table (Many-to-Many)
     * Untuk mendukung multi-user per customer
     */
    public function customers()
    {
        return $this->belongsToMany(\App\Models\Customer::class, 'customer_user')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * Get the customer this user belongs to (via pivot)
     * Returns first customer (for backward compatibility)
     */
    public function getCustomerViaRelation()
    {
        return $this->customers()->first();
    }

    /**
     * Check if user is primary PIC for any customer
     */
    public function isPrimaryPic()
    {
        return $this->customers()->wherePivot('is_primary', true)->exists();
    }

}