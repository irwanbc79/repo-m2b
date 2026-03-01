<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobCost;
use App\Models\CashTransaction;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;

class ReconcileJobCosts extends Command
{
    protected $signature = 'reconcile:job-costs {--fix : Actually fix the data, otherwise just preview} {--verbose-output : Show detailed output}';
    protected $description = 'Reconcile JobCost status with CashTransaction data. Fix records that were incorrectly marked as unpaid due to CashierService bug.';

    public function handle()
    {
        $fix = $this->option('fix');
        $verbose = $this->option('verbose-output');

        $this->info('=== Job Cost Reconciliation Tool ===');
        $this->info($fix ? '🔧 MODE: FIX (akan update data)' : '👀 MODE: PREVIEW (hanya tampilkan, tidak ubah data)');
        $this->newLine();

        // 1. Find JobCosts that are 'unpaid' but have matching paid CashTransactions
        $this->info('1️⃣  Mencari JobCost unpaid yang sudah ada CashTransaction (pembayaran)...');
        
        $unpaidJobCosts = JobCost::where('status', 'unpaid')->get();
        $this->info("   Total JobCost unpaid: {$unpaidJobCosts->count()}");

        $fixableFromCash = collect();
        foreach ($unpaidJobCosts as $jc) {
            // Check if there's a matching cash-out transaction
            $matchingCash = CashTransaction::where('shipment_id', $jc->shipment_id)
                ->where('type', 'out')
                ->where('amount', $jc->amount)
                ->when($jc->vendor_id, function ($q) use ($jc) {
                    $q->where('vendor_id', $jc->vendor_id);
                })
                ->first();

            if ($matchingCash) {
                $fixableFromCash->push([
                    'job_cost' => $jc,
                    'cash_transaction' => $matchingCash,
                ]);
            }
        }

        $this->info("   ✅ Ditemukan {$fixableFromCash->count()} JobCost yang seharusnya 'paid' (cocok dengan CashTransaction)");

        if ($verbose && $fixableFromCash->count() > 0) {
            $tableData = $fixableFromCash->map(function ($item) {
                return [
                    'ID' => $item['job_cost']->id,
                    'Shipment' => $item['job_cost']->shipment_id,
                    'Vendor' => $item['job_cost']->vendor?->name ?? 'N/A',
                    'Amount' => number_format($item['job_cost']->amount, 0, ',', '.'),
                    'Created' => $item['job_cost']->created_at?->format('d/m/Y'),
                    'Cash Tx' => $item['cash_transaction']->id,
                    'Cash Date' => $item['cash_transaction']->transaction_date,
                ];
            })->toArray();
            $this->table(['ID', 'Shipment', 'Vendor', 'Amount', 'Created', 'Cash Tx', 'Cash Date'], $tableData);
        }

        // 2. Find duplicate JobCosts (same shipment + vendor + amount + close timestamps)
        $this->newLine();
        $this->info('2️⃣  Mencari duplikat JobCost (kemungkinan dibuat dobel oleh CashierService)...');
        
        $duplicates = DB::table('job_costs as jc1')
            ->join('job_costs as jc2', function ($join) {
                $join->on('jc1.shipment_id', '=', 'jc2.shipment_id')
                    ->on('jc1.amount', '=', 'jc2.amount')
                    ->whereRaw('jc1.id < jc2.id')
                    ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE, jc1.created_at, jc2.created_at)) < 5');
            })
            ->select('jc1.id as id1', 'jc2.id as id2', 'jc1.shipment_id', 'jc1.amount', 
                'jc1.status as status1', 'jc2.status as status2',
                'jc1.created_at as created1', 'jc2.created_at as created2')
            ->get();

        $this->info("   ⚠️  Ditemukan {$duplicates->count()} kemungkinan duplikat");

        if ($verbose && $duplicates->count() > 0) {
            $dupTable = $duplicates->map(function ($d) {
                return [
                    'ID 1' => $d->id1,
                    'ID 2' => $d->id2,
                    'Shipment' => $d->shipment_id,
                    'Amount' => number_format($d->amount, 0, ',', '.'),
                    'Status 1' => $d->status1,
                    'Status 2' => $d->status2,
                ];
            })->toArray();
            $this->table(['ID 1', 'ID 2', 'Shipment', 'Amount', 'Status 1', 'Status 2'], $dupTable);
        }

        // 3. Summary before fix
        $this->newLine();
        $this->info('3️⃣  Ringkasan AP saat ini:');
        $totalUnpaidBefore = JobCost::where('status', 'unpaid')->sum('amount');
        $this->info("   Total AP (Job Costs unpaid): Rp " . number_format($totalUnpaidBefore, 0, ',', '.'));
        
        $vendorBillAP = VendorBill::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('SUM(amount_idr - paid_amount) as total')
            ->value('total') ?? 0;
        $this->info("   Total AP (Vendor Bills):     Rp " . number_format($vendorBillAP, 0, ',', '.'));
        $this->info("   Selisih:                     Rp " . number_format(abs($totalUnpaidBefore - $vendorBillAP), 0, ',', '.'));

        // 4. Apply fixes
        if ($fix) {
            $this->newLine();
            if (!$this->confirm('Yakin ingin memperbaiki data? (Tidak bisa di-undo)')) {
                $this->info('Dibatalkan.');
                return 0;
            }

            $fixCount = 0;
            DB::beginTransaction();
            try {
                // Fix JobCosts that should be paid
                foreach ($fixableFromCash as $item) {
                    $item['job_cost']->update([
                        'status' => 'paid',
                        'date_paid' => $item['cash_transaction']->transaction_date,
                    ]);
                    $fixCount++;
                }

                DB::commit();
                
                $totalUnpaidAfter = JobCost::where('status', 'unpaid')->sum('amount');
                $this->newLine();
                $this->info("✅ Berhasil memperbaiki {$fixCount} JobCost records.");
                $this->info("   AP sebelum: Rp " . number_format($totalUnpaidBefore, 0, ',', '.'));
                $this->info("   AP sesudah: Rp " . number_format($totalUnpaidAfter, 0, ',', '.'));
                $this->info("   Selisih diperbaiki: Rp " . number_format($totalUnpaidBefore - $totalUnpaidAfter, 0, ',', '.'));
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ Error: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->newLine();
            $this->warn('💡 Jalankan dengan --fix untuk memperbaiki data:');
            $this->line('   php artisan reconcile:job-costs --fix --verbose-output');
        }

        return 0;
    }
}
