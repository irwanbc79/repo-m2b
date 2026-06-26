<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCustomerRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:backfill
        {--pending : Set akun self-registrasi (punya google_id) menjadi nonaktif/pending review}
        {--dry-run : Tampilkan apa yang akan dilakukan tanpa menyimpan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat baris Customer untuk User role=customer yang belum punya (mis. pendaftar Google lama), agar muncul di Manage Customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $setPending = $this->option('pending');

        // User customer yang BELUM punya baris customer
        $orphans = User::where('role', 'customer')
            ->whereDoesntHave('customer')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('Tidak ada user customer yang orphan. Semua sudah punya baris Customer.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$orphans->count()} user customer tanpa baris Customer.");
        $created = 0;
        $deactivated = 0;

        foreach ($orphans as $user) {
            $isSelfRegistered = ! empty($user->google_id);
            $willDeactivate = $setPending && $isSelfRegistered && $user->is_active;

            $this->line(sprintf(
                ' - #%d %s <%s>%s%s',
                $user->id,
                $user->name,
                $user->email,
                $isSelfRegistered ? ' [google]' : '',
                $willDeactivate ? ' -> set PENDING' : ''
            ));

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($user, $willDeactivate, &$created, &$deactivated) {
                Customer::create([
                    'user_id' => $user->id,
                    'customer_code' => Customer::generateCustomerCode(),
                    'company_name' => $user->name ?: ('Customer ' . $user->id),
                    'business_type' => 'Regular',
                    'credit_limit' => 0,
                    'payment_terms' => 30,
                    'preferred_language' => 'id',
                ]);
                $created++;

                if ($willDeactivate) {
                    $user->update(['is_active' => false]);
                    $deactivated++;
                }
            });
        }

        if ($dryRun) {
            $this->warn('DRY RUN: tidak ada perubahan yang disimpan.');
            return self::SUCCESS;
        }

        $this->info("Selesai. {$created} baris Customer dibuat, {$deactivated} akun di-set pending.");
        return self::SUCCESS;
    }
}
