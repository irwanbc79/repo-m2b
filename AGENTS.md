# Portal M2B - Claude Code Rules

## Stack (verified 2026-07-03)
- Laravel 12 + **Livewire 3 custom (BUKAN Filament)** + Alpine.js + Tailwind CSS **v4**
- MySQL (production), SQLite in-memory (test)
- Hosting: **Hostinger shared** (LiteSpeed) — ssh -p 65002 u301249154@31.97.104.23, path `~/domains/m2b.co.id/public_html/portal`
- Backblaze B2 (backup via `backup:drive`)
- Auth: role kolom di users (admin/customer) + Socialite Google, BUKAN Spatie

## Gotcha kritis server (JANGAN dilanggar)
- **JANGAN `php artisan route:cache` di server** — Livewire update route di-override ke `/lw-update` via AppServiceProvider booted(); route:cache mematikannya.
- `public/livewire/.htaccess` di server adalah file SERVER-LOCAL (untracked) — jangan ditimpa/dihapus.
- Auto-deploy webhook: push ke main → server auto-pull. Jangan `git reset --hard` di server saat pull "conflict" — biasanya race dengan webhook, tunggu lalu cek ulang.
- Cron dikelola via hPanel Hostinger, TIDAK terlihat di `crontab -l`. Cek via API `hosting_listAccountCronJobsV1`.
- QUEUE_CONNECTION=sync di production — tidak ada queue worker.
- SSH multi-line: selalu pakai file script + `ssh ... 'bash -s' < script.sh`.

## Konvensi
- CRUD via komponen Livewire di `app/Livewire/Admin/*` + view `resources/views/livewire/admin/*`
- DB: snake_case | JS: camelCase
- Migration wajib ada method down(), dan pakai guard `Schema::hasColumn` (banyak schema drift historis)
- Gunakan php artisan make:* jangan buat file manual
- **Modal Tailwind v4**: panel modal sibling dari backdrop `fixed inset-0` WAJIB punya `style="position: relative; z-index: 10;"` (class `transform` tidak lagi membentuk stacking context; `npm run build` juga tidak selalu men-generate class z baru)

## JANGAN DIUBAH tanpa instruksi eksplisit
- File .env production
- Modul yang sudah berjalan normal
- Jangan hapus migration yang sudah jalan

## Workflow
- Backup file sebelum modifikasi (ke scratchpad/di luar repo — JANGAN commit file .bak)
- Satu task = satu branch, merge ke main
- Test lokal dulu (`php artisan test`) sebelum push — push ke main = deploy production
- Setelah deploy: `php artisan cache:clear && php artisan config:clear` di server (BUKAN route:cache)

## Monitoring
- `php artisan finance:check-integrity` — cek payment/job cost tanpa CashTransaction (terjadwal harian 07:00 WIB + email)
- `php artisan cashier:backfill-payments --dry-run` — tambal payment tanpa pembukuan
- Health endpoint: `https://portal.m2b.co.id/up`

## Deploy & kredensial
- Remote git di server memakai deploy key read-only `~/.ssh/id_ed25519_repo_m2b` (host alias `github.com-repo-m2b`), BUKAN token. Backup URL lama ada di `~/.repo_m2b_origin_backup_*`.
