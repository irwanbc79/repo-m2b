# Catatan optimisasi (tanpa mengubah flow bisnis)

## 2026-03 — batch kecil

### Admin Invoicing (`InvoiceManager`)
- **Eager load** `shipment.customer` agar nama customer dari shipment tidak memicu query tambahan per baris.
- **Hilangkan N+1** kolom "Related Invoice": satu query untuk semua shipment di halaman saat ini, map ke `$relatedInvoiceLinks` (bukan query di Blade per baris).
- **Cache statistik** `invoice_stats`: di-**flush** setelah create/edit/hapus invoice, pembayaran, revisi, generate commercial, dan upload/hapus faktur pajak — agar angka dashboard tidak tertinggal hingga 5 menit.

### Email Inbox (`EmailInbox`)
- **Hitung attachment** dengan 1 query agregat `GROUP BY email_id` (bukan ~100 query terpisah per email).

### Prinsip lanjutan
- Optimisasi berikutnya: audit Livewire lain dengan query di Blade, N+1, dan cache yang perlu invalidation.
- Refactor besar: pecah `InvoiceManager` jadi beberapa class/service hanya jika ada test/regresi manual yang jelas.
