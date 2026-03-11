# Review: Portal INSW INTR & Integrasi ke HS Code Explorer

## 1. Apa yang ada di INTR (insw.go.id/intr)

Berdasarkan review halaman INTR:

| Fitur INTR | Deskripsi |
|------------|-----------|
| **Penelusuran HS** | Cari berdasarkan kode HS atau uraian (ID/EN). Hasil: tabel HS Code, Uraian Barang (ID/EN), tombol Detail. |
| **Detail Komoditas** | Hierarki (BAB/Chapter → Heading → Subheading), uraian bilingual. |
| **Informasi Tarif** | Per kode HS: BM MFN (%), PPN (%), PPnBM, Cukai, BM AD/TP/IM, **PPH (API vs NON-API)** (%), BK. Setiap baris punya **Regulasi** (mis. PMK No 26/PMK.010/2022, PMK 131/2024). |
| **Tarif Preferensi** | Tarif preferensi (FTA, dll). |
| **Regulasi Impor (Lartas)** | Per dokumen pabean (BC 1.6, BC 2.0, BC 2.3, dll): daftar **izin yang harus dipenuhi** (nama izin, kode izin kepabeanan, komoditi, regulasi, deskripsi). |

---

## 2. Apa yang sudah ada di HS Code Explorer (Portal M2B)

- **Data:** `hs_codes` (BTKI 2022), `hs_sections`, `hs_chapters`, `hs_explanatory_notes`, `hs_kum` (KUM HS).
- **Kolom tarif di `hs_codes`:** `import_duty` (BM), `export_duty` (BK) — dari CSV BTKI; **belum** PPN, PPnBM, PPH, apalagi referensi regulasi (PMK).
- **Fitur:** Cari kode/uraian, lihat hierarki, KUM HS, explanatory notes. **Belum:** info tarif lengkap (PPN/PPnBM/PPH), link regulasi, Lartas/izin impor.

Jadi **gap utama** untuk “apply INTR” di explorer: **tarif lengkap + regulasi + (opsional) Lartas/izin**.

---

## 3. Scraping vs API

- **Scraping (ambil data dari HTML INTR):**
  - **Kelebihan:** Bisa ambil apa yang tampil di web (tarif, regulasi, Lartas).
  - **Kekurangan:** INTR tampak berat JS (“Mohon Tunggu”), kemungkinan banyak konten di-render client-side; butuh headless browser (Puppeteer/Playwright) atau reverse‑engineer request (XHR/fetch). Situs bisa berubah kapan saja; ada risiko ToS/etika dan beban server.
- **API resmi:** Ada domain `api.insw.go.id`; dokumentasi publik untuk layanan HS/tarif tidak jelas. **Rekomendasi:** cek dulu panduan resmi (mis. https://panduan.insw.go.id, katalog LNSW) apakah ada API HS/tarif yang boleh dipakai untuk integrasi.

Kesimpulan: **lebih aman dan berkelanjutan jika ada API resmi**. Kalau tidak ada, scraping bisa dipertimbangkan dengan hati‑hati (rate limit, cache, hanya untuk data yang memang tidak tersedia resmi).

---

## 4. Opsi integrasi ke HS Code Explorer

### Opsi A – Hanya tampilkan link ke INTR (effort kecil)

- Di detail HS (setelah klik Detail), tambah tombol/link: “Lihat di INTR” → `https://insw.go.id/intr/detail-komoditas?q=<hs_code>` (atau URL pencarian yang valid).
- **Efek:** User dapat info tarif/regulasi lengkap di INTR; kita tidak simpan data INTR.
- **Effort:** Sangat rendah (1–2 jam).

### Opsi B – Tarif + regulasi (sumber: INTR)

- **Scraping/API:** Ambil per HS: BM MFN, PPN, PPnBM, PPH (API/NON-API), teks regulasi (PMK).
- **Database:** Tambah tabel (mis. `hs_code_tariffs` atau kolom di `hs_codes`) + kolom “regulasi” (teks atau link).
- **Explorer:** Di panel detail HS tampilkan “Informasi Tarif” (mirip INTR) + sumber “Sumber: INTR”.
- **Efek:** Satu tempat lihat HS + tarif + regulasi; kurang ketergantungan buka INTR.
- **Effort:** Sedang–tinggi (scraping/API + normalisasi data + migration + UI + pemeliharaan saat INTR berubah).

### Opsi C – Lartas / izin impor (per dokumen BC)

- Simpan dan tampilkan “izin yang harus dipenuhi” per HS dan per tipe dokumen (BC 2.0, dll): nama izin, kode izin kepabeanan, regulasi.
- **Efek:** Sangat berguna untuk operasional impor; pembeda kuat vs hanya BTKI.
- **Effort:** Tinggi (struktur data kompleks, banyak halaman/per-HS, pemeliharaan).

---

## 5. Rekomendasi singkat

| Prioritas | Aksi | Effort | Efek |
|-----------|------|--------|------|
| 1 | **Opsi A** – Link “Lihat di INTR” di HS Code Explorer | Sangat rendah | User dapat info tarif/regulasi resmi tanpa kita scrape. |
| 2 | Cek **API resmi** INSW/INTR untuk HS & tarif; jika ada, integrasi via API (Opsi B) | Sedang | Data tarif + regulasi di portal kita, lebih stabil dari scraping. |
| 3 | Jika tidak ada API dan memang dibutuhkan, evaluasi **scraping terbatas** (tarif + regulasi) dengan cache & rate limit | Tinggi | Tarif + regulasi di explorer; perlu maintenance. |
| 4 | Opsi C (Lartas) hanya jika ada kebutuhan bisnis jelas dan sumber data (API/scrape) sudah terkendali | Sangat tinggi | Nilai tinggi untuk compliance impor. |

---

## 6. Ringkasan efek dan effort

- **Efek jika INTR “kita apply” di HS Code Explorer:**
  - User dapat **tarif lengkap** (BM, PPN, PPnBM, PPH) dan **regulasi (PMK)** tanpa keluar portal.
  - Opsional: **Lartas/izin impor** per BC → nilai untuk tim operasional/customs.
- **Effort:**
  - **Minimal (Opsi A):** 1–2 jam; hanya tambah link.
  - **Medium (Opsi B dengan API):** 1–2 minggu (integrasi API, migration, UI, testing).
  - **Tinggi (Opsi B dengan scraping atau Opsi C):** 2–4 minggu+ (scraping, normalisasi, maintenance, penanganan perubahan struktur INTR).

Disarankan mulai dengan **Opsi A** dan pengecekan **API resmi INSW**; setelah itu putuskan apakah Opsi B/C layak dengan sumber data yang dipilih.
