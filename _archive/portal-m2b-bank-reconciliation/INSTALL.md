# 🏦 Bank Reconciliation - Panduan Instalasi

## Gambaran Umum

Fitur Bank Reconciliation untuk Portal M2B memungkinkan:
- Import statement bank (CSV) dari Bank Mandiri dan BCA
- Auto-matching transaksi bank dengan pembayaran invoice
- Manual matching untuk transaksi yang tidak otomatis ter-match
- Dashboard statistik rekonsiliasi
- Tracking audit lengkap

---

## 📁 Struktur File

```
portal-m2b-bank-reconciliation/
├── app/
│   ├── Models/
│   │   ├── BankTransaction.php           # Model utama
│   │   └── InvoicePayment-addition.php   # Tambahan untuk InvoicePayment
│   ├── Services/
│   │   ├── BankStatementImportService.php   # Parser CSV
│   │   └── BankReconciliationService.php    # Logic matching
│   └── Livewire/Admin/
│       └── BankReconciliation.php        # Livewire component
├── resources/views/livewire/admin/
│   └── bank-reconciliation.blade.php     # View utama
├── routes/
│   └── bank-reconciliation-routes.php    # Routes
├── navigation/
│   └── sidebar-menu-item.blade.php       # Menu item
├── database/migrations/
│   └── 2026_01_06_add_bank_transaction_relation_to_invoice_payments.php
└── INSTALL.md                            # File ini
```

---

## 🚀 Langkah Instalasi

### 1. Copy File ke Portal M2B

```bash
# Masuk ke direktori portal
cd /home/u301249154/domains/m2b.co.id/public_html/portal

# Copy Model
cp /path/to/BankTransaction.php app/Models/

# Copy Services
cp /path/to/BankStatementImportService.php app/Services/
cp /path/to/BankReconciliationService.php app/Services/

# Copy Livewire Component
cp /path/to/BankReconciliation.php app/Livewire/Admin/

# Copy View
cp /path/to/bank-reconciliation.blade.php resources/views/livewire/admin/
```

### 2. Update Model InvoicePayment

Tambahkan method berikut ke `app/Models/InvoicePayment.php`:

```php
/**
 * Relasi ke BankTransaction
 */
public function bankTransaction()
{
    return $this->hasOne(\App\Models\BankTransaction::class, 'invoice_payment_id');
}

/**
 * Check apakah payment sudah di-reconcile
 */
public function isReconciled(): bool
{
    return $this->bankTransaction()->exists();
}
```

### 3. Tambahkan Route

Tambahkan ke `routes/web.php` di bagian admin routes:

```php
// Bank Reconciliation - Admin Only
Route::get('/admin/bank-reconciliation', \App\Livewire\Admin\BankReconciliation::class)
    ->name('admin.bank-reconciliation')
    ->middleware('role:admin');
```

### 4. Tambahkan Menu di Sidebar

Tambahkan ke file navigasi sidebar (biasanya `resources/views/layouts/navigation.blade.php`):

```php
@can('manage-admin')
<a href="{{ route('admin.bank-reconciliation') }}" 
   class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition {{ request()->routeIs('admin.bank-reconciliation') ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
    <span class="text-xl">🏦</span>
    <span>Rekonsiliasi Bank</span>
    @php
        $unreconciledCount = \App\Models\BankTransaction::unreconciled()->count();
    @endphp
    @if($unreconciledCount > 0)
        <span class="ml-auto px-2 py-0.5 text-xs bg-orange-100 text-orange-700 rounded-full">
            {{ $unreconciledCount }}
        </span>
    @endif
</a>
@endcan
```

### 5. Clear Cache

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

## ✅ Verifikasi Instalasi

1. **Cek route terdaftar:**
   ```bash
   php artisan route:list | grep bank-reconciliation
   ```

2. **Akses halaman:**
   - URL: `https://portal.m2b.co.id/admin/bank-reconciliation`
   - Login sebagai admin

3. **Test import CSV:**
   - Klik tombol "Import CSV"
   - Pilih Bank Mandiri
   - Upload file CSV statement bank
   - Periksa hasil import

---

## 📊 Fitur Utama

### Import CSV
- **Bank Mandiri**: Format semicolon (;), tanggal format `01 December 2025 11:26:05`
- **Bank BCA**: Format comma (,), tanggal format `24/12/2025`

### Auto-Matching
- Kriteria: nomor invoice di deskripsi, jumlah ±1%, tanggal ±3 hari
- Confidence score 90%+ → auto-match
- Confidence score <90% → suggestion untuk manual review

### Manual Matching
- Search by invoice number atau customer name
- Add notes untuk audit trail

### Statistik
- Total transaksi
- Sudah/belum direkonsiliasi
- Reconciliation rate
- Total kredit/debit

---

## ⚠️ Catatan Penting

1. **Database table `bank_transactions` harus sudah ada** - Jika belum, jalankan migration sebelumnya

2. **Tidak mengubah file existing** - Semua file baru, hanya perlu menambahkan method ke InvoicePayment

3. **Backup sebelum instalasi** - Selalu backup database dan file sebelum instalasi

4. **Test di environment staging dulu** - Jika ada, test dulu sebelum ke production

---

## 🔧 Troubleshooting

### Error: Class not found
```bash
composer dump-autoload
```

### Error: Route not found
```bash
php artisan route:clear
php artisan route:cache
```

### Error: View not found
```bash
php artisan view:clear
```

### CSV tidak ter-parse
- Pastikan format CSV sesuai dengan bank yang dipilih
- Check encoding file (harus UTF-8)
- Pastikan tidak ada karakter special di file

---

## 📞 Support

Jika ada masalah atau pertanyaan, hubungi tim development.

**Prepared by**: Claude (Anthropic)  
**Date**: 6 Januari 2026  
**Version**: 1.0
