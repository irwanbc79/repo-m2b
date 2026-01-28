<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeactivateInactiveCustomers extends Command
{
    protected $signature = 'customers:deactivate-inactive';
    protected $description = 'Deactivate customers with no shipments for 1 year';

    public function handle()
    {
        $cutoffDate = now()->subYear();

        $inactiveCustomers = User::where('role', 'customer')
            ->where('is_active', true)
            ->where(function ($query) use ($cutoffDate) {
                $query->where('last_shipment_at', '<', $cutoffDate)
                      ->orWhere(function ($q) use ($cutoffDate) {
                          $q->whereNull('last_shipment_at')
                            ->where('created_at', '<', $cutoffDate);
                      });
            })
            ->get();

        $count = 0;
        foreach ($inactiveCustomers as $customer) {
            DB::transaction(function () use ($customer) {
                $customer->update(['is_active' => false]);
                ActivityLog::log(
                    'updated',
                    'Customer ' . $customer->email . ' deactivated - no shipment activity for 1 year',
                    $customer
                );
            });
            $count++;
        }

        $this->info("Deactivated {$count} inactive customers.");
        return Command::SUCCESS;
    }
}
