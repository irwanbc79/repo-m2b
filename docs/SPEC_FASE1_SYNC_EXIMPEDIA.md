# Spec Fase 1 — Sinkronisasi BTKI, LARTAS & Tarif dari EximPedia ke Portal M2B

Status: **DRAF untuk keputusan Irwan** · Ditulis 2026-10-16 · Branch rencana: `feature/exim-sync`

Fase 1 = **mesin sync + data cermin + catatan perubahan**, ditambah satu tampilan
internal minimum. Fitur yang dilihat customer (Radar Lartas, peringatan perubahan,
FTA, landed cost) adalah Fase 2–3 dan dibangun di atas data Fase 1.

---

## 1. Fakta hasil pemeriksaan (2026-10-16, read-only)

### 1.1 EximPedia (Supabase, VPS srv2032616)

| Cek | Hasil |
|---|---|
| `https://eximpedia.pro/api/health.php` | `status ok, db ok, 87 ms` |
| `https://api.eximpedia.pro/rest/v1/` | 401 tanpa kunci; daftar skema (OpenAPI) untuk anon → `RBAC: access denied` (benar) |
| `https://admin.eximpedia.pro/` | 302 ke login (benar) |
| Kapasitas VPS (STATUS.md 2026-10-15) | RAM tersedia 2,27/3,9 GB, **CPU 1 inti load ~0,9 = batas utama**, PG 17.6 (195 MB, 25/100 koneksi) |
| **Masa aktif VPS** | **berakhir 2026-11-04, perpanjang otomatis MATI** |
| Metrik VPS via API Hostinger | tidak bisa — `ENOTFOUND developers.hostinger.com` dari Mac ini |

Tabel rujukan yang relevan (jumlah baris dari `Content-Range` PostgREST):

| Tabel | Baris | Akses anon | Isi penting |
|---|---|---|---|
| `hs_codes` | 11.702 (pos 8 digit) | SELECT | `hs_code`, `hs_code_clean`, `description_id/en`, `description_full(_en)`, `bm_mfn`, `ppn`, `pph_api`, `pph_non_api`, `has_lartas`, `version_year`, `is_active`, `created_at` |
| `lartas_rules` | 12.651 | SELECT | `hs_code`, `trade_type` (import/export), `regulation_scope` (BORDER/POSTBORDER), `governing_agency`, `permit_name`, `requirement_summary`, `regulation_reference`, `dok_pabean[]`, `effective_from/to`, `is_active`, `created_at` — **tanpa `updated_at`; impor = ganti penuh (id berubah)** |
| `data_freshness` | 3 | SELECT | `lartas_rules` as_of **2026-10-10**, 12.651 baris |
| `ref_dokumen_pabean` | 13 | SELECT | kode → BC 1.6, BC 2.0, … |
| `ref_government_agencies` | 103 | SELECT | `kd_ga` → instansi |
| `hs_explanatory_notes` | 1.551 | SELECT | Catatan Penjelasan (EN-INA) per pos |
| `hs_tariff_source` | ±11.512 | **ditolak** | `bm_mfn_insw`, `bm_value_raw`, `legal_basis` (mis. PMK 50/2026), `status` (sesuai/diperbarui/perlu_verifikasi/non_advalorem), `snapshot_date` |
| `hs_tariff_temporary` | 6 | **ditolak** | tarif sementara (PMK 68/2026 Ps.4B, s.d. 2027-04-08) |
| `hs_tariff_preference` | 365.726 | **ditolak** | FTA per skema → **Fase 3** |
| `fta_scheme_country` | ±51 | **ditolak** | skema → negara → **Fase 3** |
| `regulatory_change_logs` | ? | **ditolak** | perubahan peraturan dari pemantau JDIH → **Fase 2** |

### 1.2 Portal M2B (repo-m2b @ `01eaf22`)

| Bagian | Keadaan | Dampak ke desain |
|---|---|---|
| `hs_codes` | 14.631 baris, hierarki 2/4/6/8/10 digit, `hs_code` bertitik, dipakai FK `hs_favorites` & `hs_explanatory_notes` | **Jangan ditimpa.** Cermin EximPedia masuk tabel terpisah, ditautkan lewat kode ternormalisasi (8 digit). |
| `hs_codes.import_duty/export_duty` | ada di produksi, **tidak ada di migration** (drift) | Jangan dijadikan sumber tarif; tarif diambil dari cermin. |
| `lartas_references` | ~9 baris, diisi staf, unik `(hs_code, trade_flow)` | Tetap jadi lapisan **koreksi manusia** di atas data INSW. |
| `LartasReference::lookup()` | memuat **semua** baris ke PHP lalu memfilter | Aman untuk 9 baris; untuk cermin 12 ribu baris perlu kolom ternormalisasi berindeks. |
| `tax_exchange_rates` + `kurs:fetch-pajak` | scrape fiskal.kemenkeu.go.id harian 08:00 WIB | EximPedia **tidak** punya kurs → portal tetap jadi sumber. Fase 1 hanya menambah pemantau kesegaran. |
| `CustomsCalculator` (admin & customer) | tarif BM diketik manual | Fase 1 menyiapkan data; pengisian otomatis (internal) masuk Fase 2/3. |
| `LartasAiService` | AI cenderung menebak terlalu banyak | Fase 1 memberi data INSW sebagai dasar (grounding) yang jauh lebih luas. |

---

## 2. Keputusan arsitektur

### 2.1 Pola: portal **menarik** data terjadwal ke tabel cermin MySQL (pull + snapshot)

Dipilih dibanding baca langsung tiap permintaan (portal ikut mati bila VPS mati,
latensi lintas server) dan dibanding dikirim dari Supabase (webhook ke hosting
shared rapuh, sulit diulang). Konsekuensi yang diterima: data tertinggal maksimal
1 hari — cukup, karena LARTAS/tarif berubah dalam hitungan minggu.

**Prinsip:**
1. EximPedia = sumber utama; portal = salinan **baca-saja** yang menyimpan versi.
2. Sync gagal → data terakhir yang sah **tetap dipakai**, tidak pernah dikosongkan.
3. Setiap baris membawa `source_as_of` dan `synced_at`; UI selalu menampilkan "per tanggal".
4. Setiap perbedaan antar-sync dicatat (`exim_change_events`) → dasar Fase 2 (peringatan).

### 2.2 Jalur akses — 2 opsi (perlu keputusan Irwan)

| | **A. Role khusus `m2b_sync` + JWT (rekomendasi)** | B. Anon key publik |
|---|---|---|
| Data yang terjangkau | semua yang di-GRANT: hs_codes, lartas_rules, **tarif INSW + tarif sementara**, nanti FTA & change log | hanya tabel publik — **tanpa tarif INSW & tarif sementara** |
| Keamanan | hak minimum, bisa dicabut tanpa mengganggu situs; jejak di log PG per role | kunci sudah publik; tidak bisa dibedakan dari pengunjung |
| Pekerjaan di VPS | 1 migration SQL + buat token (Irwan jalankan) | tidak ada |
| Risiko | token bocor → hanya bisa **membaca** data rujukan | anon policy berubah → sync diam-diam putus |

Opsi A secara teknis:

```sql
-- eximpedia/migrations/2026-10-xx_m2b_sync_role.sql (draf, Irwan yang menjalankan)
create role m2b_sync nologin;
grant m2b_sync to authenticator;           -- PostgREST boleh beralih ke role ini dari klaim JWT
grant usage on schema public to m2b_sync;

-- Kontrak stabil: VIEW, bukan tabel mentah — EximPedia bebas merombak tabel
-- selama view tetap sama.
create or replace view public.m2b_sync_hs_codes_v1 as
  select h.hs_code_clean, h.hs_code, h.description_id, h.description_en,
         h.description_full, h.description_full_en, h.bm_mfn, h.ppn,
         h.pph_api, h.pph_non_api, h.has_lartas, h.version_year, h.is_active,
         s.bm_value_raw, s.legal_basis as bm_legal_basis, s.status as bm_status,
         s.snapshot_date as bm_snapshot_date
  from public.hs_codes h
  left join public.hs_tariff_source s using (hs_code_clean);

create or replace view public.m2b_sync_lartas_v1 as
  select hs_code, trade_type, regulation_scope, governing_agency, permit_name,
         requirement_summary, regulation_reference, dok_pabean,
         effective_from, effective_to, is_active, created_at
  from public.lartas_rules;

create or replace view public.m2b_sync_tariff_temporary_v1 as
  select hs_code_clean, bm_rate, legal_basis, berlaku_mulai, berlaku_sampai,
         sampai_dihitung, sumber_url
  from public.hs_tariff_temporary;

revoke all on public.m2b_sync_hs_codes_v1, public.m2b_sync_lartas_v1,
              public.m2b_sync_tariff_temporary_v1 from anon, authenticated;
grant select on public.m2b_sync_hs_codes_v1, public.m2b_sync_lartas_v1,
                public.m2b_sync_tariff_temporary_v1, public.data_freshness to m2b_sync;
```

- View dipasang di skema `public` supaya **tidak perlu mengubah `PGRST_DB_SCHEMAS`/compose** (VPS sensitif).
- Token: JWT `{"role":"m2b_sync","iss":"eximpedia","exp":<+180 hari>}` ditandatangani `JWT_SECRET` VPS, dibuat oleh skrip baru `scripts/vps/08_m2b_sync_token.sh` (dijalankan Irwan; token **tidak lewat chat**).
- Portal: `.env` produksi `EXIM_SYNC_URL=https://api.eximpedia.pro/rest/v1` + `EXIM_SYNC_TOKEN=…` (diisi Irwan sendiri). Kedaluwarsa token → sync gagal dengan pesan jelas + email peringatan 14 hari sebelum `exp`.

---

## 3. Skema data di portal (migration baru, semuanya punya `down()`)

```
exim_sync_runs
  id, source_key (hs_codes|lartas|tariff_temporary), started_at, finished_at,
  status (running|ok|skipped_unchanged|failed|blocked_guard),
  source_as_of (date), source_row_count, rows_fetched,
  n_added, n_changed, n_removed, error (text), triggered_by (schedule|manual:<user_id>)

exim_hs_codes
  hs_code_clean CHAR(8) PK, hs_code VARCHAR(20) (bertitik, sesuai portal),
  description_id, description_en, description_full, description_full_en (TEXT),
  bm_mfn DECIMAL(8,2) NULL, bm_value_raw VARCHAR(100) NULL, bm_legal_basis VARCHAR(150) NULL,
  bm_status VARCHAR(30) NULL, bm_snapshot_date DATE NULL,
  ppn, pph_api, pph_non_api DECIMAL(6,2) NULL,
  has_lartas BOOL, version_year SMALLINT, is_active BOOL,
  row_hash CHAR(40), source_as_of DATE, first_seen_at, last_seen_at, removed_at NULL
  INDEX(hs_code), INDEX(is_active)

exim_lartas_rules
  id, natural_key CHAR(40) UNIQUE  -- sha1(hs_code_clean|trade_type|scope|permit_name|regulation_reference)
  hs_code_clean CHAR(8) INDEX, trade_type ENUM(import,export), regulation_scope VARCHAR(20),
  governing_agency, permit_name, requirement_summary (TEXT), regulation_reference (TEXT),
  dok_pabean JSON, effective_from DATE NULL, effective_to DATE NULL, is_active BOOL,
  row_hash CHAR(40), source_as_of DATE, first_seen_at, last_seen_at, removed_at NULL
  INDEX(hs_code_clean, trade_type, is_active)

exim_tariff_temporary
  id, hs_code_clean CHAR(8) INDEX, bm_rate DECIMAL(8,2), legal_basis, berlaku_mulai, berlaku_sampai,
  sampai_dihitung BOOL, sumber_url, natural_key UNIQUE, row_hash, synced_at, removed_at NULL

exim_change_events            -- dasar peringatan Fase 2; append-only
  id, run_id FK exim_sync_runs, source_key, hs_code_clean INDEX, trade_type NULL,
  change_type ENUM(added,changed,removed), natural_key, before JSON NULL, after JSON NULL,
  detected_at, reviewed_at NULL, reviewed_by NULL, review_note NULL
```

Catatan desain:
- Migration dibuat via `php artisan make:migration`, tiap `create` dijaga `Schema::hasTable` dan tiap tambah kolom dijaga `Schema::hasColumn` (konvensi repo — banyak drift historis), semua punya `down()`. Test memakai SQLite in-memory → hindari tipe khusus MySQL selain yang sudah dipakai repo.
- **Kunci alami, bukan `id` EximPedia.** `lartas_rules` diimpor ulang "ganti penuh" (id UUID berubah tiap impor), jadi identitas baris = hash isi kunci bisnisnya. Ini juga mencegah impor ulang tercatat sebagai "12.651 dihapus + 12.651 ditambah".
- **Hapus = `removed_at`, bukan DELETE.** Quotation/shipment lama tetap bisa menunjukkan aturan yang berlaku saat itu.
- Bentuk HS portal (bertitik) dibuat dari `hs_code_clean` lewat `HsCodeFormatter` (satu-satunya sumber aturan bentuk HS) — jangan menulis aturan format baru.

---

## 4. Mesin sync

Command: `php artisan exim:sync {--source=all|hs_codes|lartas|tariff_temporary} {--dry-run} {--force}`
Service: `App\Services\EximSync\EximSyncService` (+ `EximClient` tipis di atas `Http::`).

Alur per sumber:
1. **Cek kesegaran dulu** (`data_freshness`, 1 permintaan). Kalau `as_of` + `row_count` sama dengan run `ok` terakhir dan bukan `--force` → `skipped_unchanged`. (Untuk sumber tanpa baris freshness, misalnya tarif: selalu tarik — kecil.)
2. **Tarik per halaman** `?select=...&order=hs_code_clean&limit=1000&offset=n` dengan header `Prefer: count=exact`. hs_codes ±12 permintaan, lartas ±13. Timeout 30 dtk, retry 3× dengan jeda bertingkat.
3. **Validasi sebelum menulis** (penjaga/circuit breaker):
   - `rows_fetched` harus sama dengan `Content-Range` total → kalau tidak, gagal (halaman hilang).
   - Kalau baris aktif turun **>5%** dibanding cermin saat ini → `blocked_guard`, tidak ada yang ditulis, email peringatan. Lanjut hanya dengan `--force` oleh manusia. (Melindungi dari impor parsial di EximPedia.)
   - Kolom wajib kosong / HS bukan 8 digit → baris ditolak dan dihitung; >1% ditolak → gagal.
4. **Terapkan dalam satu transaksi**: upsert per 500 baris berdasarkan `natural_key`; bandingkan `row_hash` → `changed` bila beda; baris di cermin yang tidak muncul lagi → `removed_at = now()`; tiap beda → `exim_change_events`.
5. Tutup run (`exim_sync_runs`) + `Cache::forget` kunci lookup.

Jadwal (`routes/console.php`):
```php
Schedule::command('exim:sync')->dailyAt('03:30')->timezone('Asia/Jakarta')
    ->withoutOverlapping(30)->onOneServer();
```
03:30 dipilih karena di luar backup portal (02:00/02:15) dan pemantau EximPedia (20:17), dan VPS (1 inti) sedang sepi. Beban: ±30 permintaan HTTP/hari — dapat diabaikan.

Hosting shared: tanpa queue (`QUEUE_CONNECTION=sync`), jadi command berjalan langsung dari `schedule:run`. Perkiraan memori: ≤64 MB (diproses per halaman, tidak menampung 12 ribu baris sekaligus). **JANGAN `route:cache`** (aturan portal).

---

## 5. Pemakaian data di Fase 1 (internal saja)

1. **Resolver LARTAS berlapis** — `App\Services\LartasResolver::for(string $hs, string $flow)`:
   1. `lartas_references` (catatan staf) → **keputusan manusia, menang** bila ada & belum kedaluwarsa (`STALE_DAYS`).
   2. `exim_lartas_rules` aktif, cocok persis 8 digit → "INSW via EximPedia per <source_as_of>".
   3. Tidak ada keduanya → "belum ada data" (BUKAN "bebas lartas"). Bebas hanya bila `exim_hs_codes.has_lartas = false` untuk arah itu **atau** staf menandai bebas.
   4. AI tetap hanya perkiraan sekunder (perilaku sekarang), tapi prompt diberi hasil 1–2 sebagai dasar.
   - Perbaiki `LartasReference::lookup()`: tambah kolom `hs_code_clean` berindeks (migration + backfill) dan query SQL, bukan `->get()` semua baris.
2. **Panel LARTAS di admin ShipmentDetail**: daftar aturan per arah (border/postborder, instansi, izin, dasar hukum, dokumen pabean) + tanggal data + badge "data > 30 hari" bila `source_as_of` sudah tua.
3. **Halaman `/admin/exim-sync`** (admin saja): riwayat run, status per sumber, umur data, tombol "Sync sekarang" (manual, tercatat `triggered_by`), daftar `exim_change_events` 30 hari terakhir.
4. **Laporan selisih BTKI** (dibaca saja): kode 8 digit di portal yang tidak ada di EximPedia dan sebaliknya (mis. 150 pos Bab 98 PMK 50/2026). **Tidak** menyisipkan/mengubah `hs_codes` portal di Fase 1.
5. **Kurs**: `kurs:check-freshness` — bila tidak ada kurs pajak yang berlaku hari ini untuk USD → email ke `FINANCE_ALERT_EMAIL` (masuk ke jadwal `finance:check-integrity` 07:00 yang sudah ada). `kurs:fetch-pajak` tidak diubah.

Yang **tidak** disentuh di Fase 1: modul quotation, kalkulator bea, portal customer, `hs_codes` portal, struktur shipment.

---

## 6. Pengujian (lokal, `Http::fake` + fixture JSON dari sampel live)

- Sync pertama: N baris ditambah, change events = added.
- Sync kedua, data sama → `skipped_unchanged`, 0 event.
- Impor ulang "ganti penuh" (id berubah, isi sama) → 0 changed / 0 removed (bukti kunci alami bekerja).
- 1 baris berubah `regulation_reference` → 1 `changed` dengan before/after.
- Penjaga: turun 6% → `blocked_guard`, cermin tidak berubah; `--force` → diterapkan.
- Halaman hilang (`Content-Range` ≠ terambil) → gagal, cermin tidak berubah.
- Token 401 / timeout → gagal dengan pesan jelas, data lama tetap dilayani.
- Resolver: catatan staf menang atas INSW; staf kedaluwarsa → INSW + peringatan; tidak ada data → "belum ada data", bukan "bebas".
- `LartasReference::lookup()` lewat SQL memberi hasil sama dengan versi lama (regresi).
- Seluruh suite yang ada tetap hijau.

---

## 7. Urutan kerja & siapa

| # | Langkah | Siapa |
|---|---|---|
| 0a | **Perpanjang VPS EximPedia sebelum 2026-11-04** (atau nyalakan perpanjang otomatis) | Irwan |
| 0b | Putuskan opsi akses A/B (§2.2) | Irwan |
| 1 | (Opsi A) tulis migration `m2b_sync_role.sql` + skrip `08_m2b_sync_token.sh` di repo eximpedia, diuji di PG lokal | Claude |
| 2 | Jalankan migration + buat token di VPS, isi `.env` portal | Irwan |
| 3 | Branch `feature/exim-sync`: migrations, service, command, test (§3–§6) | Claude |
| 4 | Uji lokal dengan `--dry-run` terhadap API live (baca saja) | Claude |
| 5 | Review + merge → deploy (auto-deploy webhook) → `exim:sync` pertama manual, cek `/admin/exim-sync` | Irwan + Claude |
| 6 | Pantau 7 hari (run harian ok, 0 blocked_guard palsu) → buka Fase 2 | bersama |

Kriteria selesai Fase 1:
- `exim_hs_codes` ≈ 11.702 dan `exim_lartas_rules` ≈ 12.651 baris aktif di produksi, `source_as_of` cocok dengan `data_freshness`.
- 7 run harian berturut-turut `ok`/`skipped_unchanged`.
- Panel LARTAS di admin ShipmentDetail menampilkan aturan INSW untuk shipment ber-HS (contoh uji: 2306.60.10 impor = Karantina, ekspor = bebas — harus cocok dengan catatan staf yang sudah ada).
- Mematikan EximPedia sementara (simulasi: token salah) **tidak** mengganggu portal; hanya muncul run `failed` + email.

---

## 8. Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| VPS EximPedia habis masa aktif / mati | Cermin tetap melayani data terakhir; badge umur data; email gagal sync. Prasyarat 0a. |
| Data LARTAS EximPedia sendiri tertinggal (as_of 2026-10-10) | Tanggal per sumber selalu ditampilkan; > 30 hari → peringatan. Penyegaran data adalah tugas EximPedia (pipeline INSW), bukan portal. |
| API INSW (sumber hulu) tidak resmi & bisa ditutup | Portal tidak pernah memanggil INSW langsung; hanya EximPedia. Bila hulu putus, cermin + umur data tetap jujur. |
| Impor parsial di EximPedia menghapus aturan di portal | Penjaga >5% + kunci alami + `removed_at` (bisa dipulihkan). |
| Salah tafsir "tidak ada data" = "bebas" | Resolver membedakan keduanya secara eksplisit (§5.1). |
| Beban VPS 1 inti | ±30 permintaan/hari jam 03:30; dapat diabaikan. |
| Token bocor | Role hanya bisa SELECT view rujukan; rotasi lewat `08_m2b_sync_token.sh`; `exp` 180 hari. |
| Tanggung jawab hukum | Semua tampilan tetap berlabel "informasi rujukan, keputusan akhir mengikuti INSW/peraturan" — pola disclaimer yang sudah ada di panel lartas. |

---

## 9. Pertanyaan terbuka untuk Irwan

1. Opsi akses **A (role + JWT, rekomendasi)** atau **B (anon key, tanpa tarif)**?
2. Laporan selisih BTKI cukup dilaporkan saja di Fase 1 (rekomendasi), atau kode baru (mis. Bab 98) langsung disisipkan ke `hs_codes` portal?
3. Siapa penerima email gagal sync — `FINANCE_ALERT_EMAIL` atau alamat IT/ops terpisah?
