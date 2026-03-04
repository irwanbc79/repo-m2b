# Deploy & Backup – Portal M2B

Dokumen ini berisi checklist deploy dan cara backup **tanpa mengganggu flow** yang sudah jalan.

---

## 1. Backup (sudah tersedia di codebase)

### Backup otomatis (scheduler)

| Waktu (WIB) | Command | Isi |
|-------------|---------|-----|
| 02:00 | `backup:drive` | Dokumen (folder `documents`) ke Backblaze B2 |
| **02:15** | **`backup:database`** | **Database ke `storage/app/backups/`** (lokal) |
| 02:30 | `email:cleanup` | Bersihkan attachment email (TTL) |

Backup database **tidak mengubah** aplikasi yang jalan; hanya menambah jadwal di scheduler.

### Backup database manual

```bash
# Backup DB saja (MySQL/SQLite), simpan 7 terakhir
php artisan backup:database

# Sertakan salinan .env (hanya di server, jangan commit)
php artisan backup:database --env

# Simpan 14 backup terakhir
php artisan backup:database --keep=14
```

Lokasi file: `storage/app/backups/`  
- MySQL: `db_mysql_YYYY-MM-DD_HH-ii-ss.sql.gz`  
- SQLite: `db_sqlite_YYYY-MM-DD_HH-ii-ss.sqlite`

**Penting:** Folder `storage/app/backups/` sudah di-.gitignore. Backup tidak ikut ke Git.

### Restore dari backup

- **MySQL:** `gunzip -c db_mysql_*.sql.gz | mysql -u user -p nama_database`
- **SQLite:** ganti file `database/database.sqlite` dengan file backup, sesuaikan permission.

---

## 2. Checklist deploy (tanpa ganggu flow)

Lakukan **setelah** pull/update code:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan view:clear
# Jika pakai queue:
# php artisan queue:restart
```

Pastikan:

- [ ] `.env` production: `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Crontab scheduler: `* * * * * cd /path-to-repo && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Folder `storage/app/backups` writable (bisa dibuat otomatis oleh command)

---

## 3. Yang tidak berubah

- Route dan middleware tetap sama.
- Jadwal yang sudah ada (`backup:drive`, `kurs:fetch-pajak`, `email:cleanup`, `cache:clear`) tetap jalan.
- Hanya **ditambah** satu jadwal: `backup:database` jam 02:15.

Jika tidak ingin backup DB otomatis, hapus atau comment blok `Schedule::command('backup:database')` di `routes/console.php`.
