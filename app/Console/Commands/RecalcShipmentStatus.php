<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use Illuminate\Console\Command;

/**
 * Koreksi status shipment yang "melebihi" bukti dokumennya — akibat bug lama
 * (upload Billing Pungutan/SPJM keliru men-set status='customs_released').
 *
 * Hanya menurunkan status yang AHEAD-of-documents ke milestone dokumen terjauh
 * yang sebenarnya (mis. customs_released → billing_issued bila belum ada SPPB).
 * TIDAK menyentuh status final (completed/cancel) & tidak menaikkan apa pun.
 */
class RecalcShipmentStatus extends Command
{
    protected $signature = 'shipments:recalc-status
        {--dry-run : Preview tanpa menulis}
        {--yes : Lewati konfirmasi (SSH non-interaktif)}';

    protected $description = 'Koreksi status shipment yang melebihi bukti dokumen (bug auto-status lama)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $affected = Shipment::whereNotIn('status', ['completed', 'cancel', 'cancelled'])
            ->with('documents')
            ->get()
            ->map(function ($s) {
                $docMilestone = $s->computeMilestoneFromDocuments();
                return [
                    'shipment' => $s,
                    'target'   => $docMilestone,
                ];
            })
            // Hanya yang status-nya AHEAD dari bukti dokumen (perlu diturunkan).
            ->filter(function ($r) {
                $s = $r['shipment'];
                $target = $r['target'];
                return $target
                    && $s->status !== $target
                    && $s->statusOrder() > $s->statusOrder($target);
            })
            ->values();

        if ($affected->isEmpty()) {
            $this->info('Tidak ada shipment yang statusnya melebihi bukti dokumen. Semua konsisten.');
            return self::SUCCESS;
        }

        $this->info("Shipment yang perlu dikoreksi: {$affected->count()}");
        $this->table(
            ['ID', 'Reference', 'Service', 'Status sekarang', '→ Koreksi ke'],
            $affected->map(fn ($r) => [
                $r['shipment']->id,
                $r['shipment']->awb_number ?: $r['shipment']->bl_number ?: ('#' . $r['shipment']->id),
                $r['shipment']->service_type,
                $r['shipment']->status,
                $r['target'],
            ])
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] Tidak ada data yang ditulis.');
            return self::SUCCESS;
        }

        if (!$this->option('yes') && !$this->confirm("Koreksi {$affected->count()} shipment?")) {
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($affected as $r) {
            $r['shipment']->update(['status' => $r['target']]);
            $count++;
        }

        $this->info("Selesai. {$count} shipment dikoreksi.");

        return self::SUCCESS;
    }
}
