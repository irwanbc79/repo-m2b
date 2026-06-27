<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    // Guarded kosong = Semua kolom boleh diisi (Aman untuk Mass Assignment)
    protected $guarded = [];

    protected $casts = [
        'profile_reminder_seen_at' => 'datetime',
        'profile_reminder_dismissed_at' => 'datetime',
        'profile_completed_at' => 'datetime',
    ];

    /**
     * Status siklus pengingat data untuk admin (lihat di Manage Customers).
     * Mengembalikan label + warna yang merangkum reaksi customer terhadap banner.
     *
     * @return array{label:string, level:string}|null  null jika data sudah lengkap & lama
     */
    public function reminderStatus(): ?array
    {
        // Sudah lengkap -> tampilkan badge "baru dilengkapi" bila masih hangat (7 hari).
        if ($this->profile_completed_at) {
            if ($this->profile_completed_at->gt(now()->subDays(7))) {
                return ['label' => 'Baru dilengkapi', 'level' => 'done'];
            }
            return null; // sudah lengkap & lama -> tidak perlu badge
        }

        if ($this->dataQuality()['level'] === 'good') {
            return null; // lengkap tapi belum ter-stempel (mis. diisi admin) -> abaikan
        }

        if ($this->profile_reminder_dismissed_at) {
            return ['label' => 'Ditutup customer', 'level' => 'dismissed'];
        }
        if ($this->profile_reminder_seen_at) {
            return ['label' => 'Sudah lihat', 'level' => 'seen'];
        }
        return ['label' => 'Belum lihat', 'level' => 'unseen'];
    }

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
     * Scope: customer yang datanya perlu dilengkapi/diverifikasi.
     * Dipakai admin untuk memfilter data "asal isi" (mis. nama tidak jelas,
     * tanpa NPWP/telepon/alamat) yang tidak bisa dipakai untuk dokumen pabean
     * maupun invoice. DB-agnostic (MySQL & SQLite) — tanpa REGEXP.
     */
    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('npwp')->orWhere('npwp', '')
              ->orWhereNull('phone')->orWhere('phone', '')
              ->orWhereNull('address')->orWhere('address', '')
              ->orWhereRaw('LENGTH(TRIM(company_name)) < 4');
        });
    }

    /**
     * Deteksi nama perusahaan yang kemungkinan besar placeholder/asal isi
     * (mis. "Doyyan xD", "test", "asdff"). Heuristik konservatif — hanya
     * menandai pola yang jelas mencurigakan agar minim false positive.
     */
    public function hasSuspectName(): bool
    {
        $name = trim((string) $this->company_name);

        if ($name === '' || mb_strlen($name) < 3) {
            return true;
        }
        // Kata kunci placeholder / iseng
        if (preg_match('/\b(test|testing|coba|cobacoba|asdf|qwerty|wkwk+|haha+|hehe+|xd|dummy|ngetes|sample|lorem|aaa+)\b/iu', $name)) {
            return true;
        }
        // Karakter berulang panjang: "aaaa", "xxxx"
        if (preg_match('/(.)\1{3,}/u', $name)) {
            return true;
        }
        // Mengandung emoji / simbol non-bisnis
        if (preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $name)) {
            return true;
        }
        return false;
    }

    /**
     * Skor kelengkapan & kualitas data customer (0-100) beserta daftar
     * masalahnya. Dipakai untuk badge di Manage Customers dan dasar pesan
     * pengingat ke customer agar melengkapi data dengan benar.
     *
     * @return array{score:int, level:string, issues:array<int,string>}
     */
    public function dataQuality(): array
    {
        $issues = [];
        $score = 0;

        $name = trim((string) $this->company_name);
        if ($this->hasSuspectName()) {
            $issues[] = 'Nama perusahaan terlihat tidak valid / belum jelas';
        } elseif ($name !== '') {
            $score += 30;
        } else {
            $issues[] = 'Nama perusahaan kosong';
        }

        $checks = [
            'npwp'    => ['NPWP belum diisi', 20],
            'phone'   => ['No. telepon belum diisi', 15],
            'address' => ['Alamat belum diisi', 10],
            'city'    => ['Kota belum diisi', 10],
        ];
        foreach ($checks as $field => [$label, $weight]) {
            if (filled($this->{$field})) {
                $score += $weight;
            } else {
                $issues[] = $label;
            }
        }

        // Bonus kelengkapan profil
        if (filled($this->trade_type)) {
            $score += 5;
        } else {
            $issues[] = 'Kebutuhan layanan belum dipilih';
        }
        if (filled($this->position)) {
            $score += 5;
        }
        if (filled($this->business_type) && $this->business_type !== 'Regular') {
            $score += 5;
        }

        $score = min(100, $score);

        $level = 'good';
        if ($this->hasSuspectName() || $score < 40) {
            $level = 'bad';
        } elseif ($score < 75) {
            $level = 'warn';
        }

        return ['score' => $score, 'level' => $level, 'issues' => $issues];
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
