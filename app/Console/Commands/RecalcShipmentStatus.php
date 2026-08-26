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

        // Rekonsiliasi lane_status yang belum terisi di database
        $laneAffected = Shipment::with('documents')
            ->get()
            ->map(function ($s) {
                $targetLane = $s->computeLaneStatusFromDocuments();
                return [
                    'shipment'   => $s,
                    'targetLane' => $targetLane,
                ];
            })
            ->filter(function ($r) {
                $s = $r['shipment'];
                $targetLane = $r['targetLane'];
                return $targetLane && empty($s->lane_status);
            })
            ->values();

        if ($laneAffected->isNotEmpty()) {
            $this->info("Shipment dengan penjaluran (lane_status) yang belum terisi: {$laneAffected->count()}");
            $this->table(
                ['ID', 'Reference', 'Service', 'Dokumen Penjaluran', '→ Set Lane'],
                $laneAffected->map(fn ($r) => [
                    $r['shipment']->id,
                    $r['shipment']->awb_number ?: $r['shipment']->bl_number ?: ('#' . $r['shipment']->id),
                    $r['shipment']->service_type,
                    $r['shipment']->documents->pluck('description')->implode(', '),
                    $r['targetLane'] === 'green' ? '🟩 Jalur Hijau' : '🟥 Jalur Merah',
                ])
            );

            if (!$dryRun) {
                if ($this->option('yes') || $this->confirm("Update lane_status untuk {$laneAffected->count()} shipment ini?")) {
                    $laneCount = 0;
                    foreach ($laneAffected as $r) {
                        $r['shipment']->update(['lane_status' => $r['targetLane']]);
                        $laneCount++;
                    }
                    $this->info("Selesai. {$laneCount} shipment diperbarui status jalurnya.");
                }
            }
        }

        return self::SUCCESS;
    }
}
