# Portal M2B - Claude Code Rules

## Stack
- Laravel 12 + Filament PHP v3
- Spatie RBAC (roles & permissions)
- MySQL
- Backblaze B2 (storage)
- VPS SkyGuard

## Konvensi
- Semua CRUD via Filament Resource
- DB: snake_case | JS: camelCase
- Migration wajib ada method down()
- Gunakan php artisan make:* jangan buat file manual

## JANGAN DIUBAH
- Modul Payroll (production)
- AuthServiceProvider.php
- File .env.production
- Semua modul yang sudah berjalan normal

## Workflow
- Backup file sebelum modifikasi
- Satu task = satu PR/branch
- Test lokal dulu sebelum push ke production
