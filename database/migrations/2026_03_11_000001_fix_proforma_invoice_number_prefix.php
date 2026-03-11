<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sesuaikan nomor invoice Proforma: ganti prefix INV/ menjadi PRO/
     * supaya data lama konsisten tanpa perlu dihapus oleh staf.
     */
    public function up(): void
    {
        $rows = DB::table('invoices')
            ->whereRaw("LOWER(TRIM(type)) = 'proforma'")
            ->where('invoice_number', 'like', 'INV/%')
            ->get(['id', 'invoice_number']);

        foreach ($rows as $row) {
            $newNumber = 'PRO' . substr($row->invoice_number, 3); // INV/2603/4582 -> PRO/2603/4582

            $exists = DB::table('invoices')
                ->where('invoice_number', $newNumber)
                ->where('id', '!=', $row->id)
                ->exists();

            if (!$exists) {
                DB::table('invoices')->where('id', $row->id)->update(['invoice_number' => $newNumber]);
                continue;
            }

            // Jika PRO/yyMM/xxxx sudah dipakai, pakai nomor urut berikutnya untuk bulan itu
            $yearMonth = substr($newNumber, 4, 4); // 2603
            $used = DB::table('invoices')->where('invoice_number', 'like', "PRO/{$yearMonth}/%")->pluck('invoice_number');
            $seq = 1;
            foreach ($used as $num) {
                if (preg_match('/\/(\d+)$/', $num, $m)) {
                    $n = (int) $m[1];
                    if ($n >= $seq) $seq = $n + 1;
                }
            }
            do {
                $fallback = sprintf('PRO/%s/%04d', $yearMonth, $seq);
                $exists = DB::table('invoices')->where('invoice_number', $fallback)->where('id', '!=', $row->id)->exists();
                if ($exists) $seq++;
            } while ($exists);
            DB::table('invoices')->where('id', $row->id)->update(['invoice_number' => $fallback]);
        }
    }

    public function down(): void
    {
        // Tidak di-revert: mengembalikan PRO -> INV bisa bentrok dengan data yang sah
    }
};
