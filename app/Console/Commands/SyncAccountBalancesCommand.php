<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;

class SyncAccountBalancesCommand extends Command
{
    protected $signature = 'accounting:sync-balances';
    protected $description = 'Recalculate and synchronize account balances from General Ledger (journal_items)';

    public function handle(): int
    {
        $this->info('Starting account balance synchronization...');

        $accounts = Account::orderBy('code')->get();
        $updatedCount = 0;

        foreach ($accounts as $account) {
            $oldBalance = (float) $account->current_balance;
            $newBalance = $account->recalculateBalance();

            if (abs($oldBalance - $newBalance) > 0.001) {
                $this->line(sprintf(
                    '[%s] %s: Rp %s -> Rp %s (Diff: Rp %s)',
                    $account->code,
                    $account->name,
                    number_format($oldBalance, 0, ',', '.'),
                    number_format($newBalance, 0, ',', '.'),
                    number_format($newBalance - $oldBalance, 0, ',', '.')
                ));
                $updatedCount++;
            }
        }

        $this->info("Completed. Synchronized {$updatedCount} accounts out of {$accounts->count()} total accounts.");

        return self::SUCCESS;
    }
}
