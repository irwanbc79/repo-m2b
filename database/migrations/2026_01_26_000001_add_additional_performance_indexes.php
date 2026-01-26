<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index untuk invoices - due_date (sering dipakai untuk overdue check)
        if (!$this->indexExists('invoices', 'idx_invoices_due_date')) {
            DB::statement('CREATE INDEX idx_invoices_due_date ON invoices(due_date)');
        }
        
        // Index untuk invoices - status + due_date (combo untuk overdue)
        if (!$this->indexExists('invoices', 'idx_invoices_status_due')) {
            DB::statement('CREATE INDEX idx_invoices_status_due ON invoices(status, due_date)');
        }
        
        // Index untuk invoices - payment_date
        if (!$this->indexExists('invoices', 'idx_invoices_payment_date')) {
            DB::statement('CREATE INDEX idx_invoices_payment_date ON invoices(payment_date)');
        }
        
        // Index untuk quotations - status
        if (Schema::hasTable('quotations') && !$this->indexExists('quotations', 'idx_quotations_status')) {
            DB::statement('CREATE INDEX idx_quotations_status ON quotations(status)');
        }
        
        // Index untuk quotations - valid_until (expiry check)
        if (Schema::hasTable('quotations') && !$this->indexExists('quotations', 'idx_quotations_valid')) {
            DB::statement('CREATE INDEX idx_quotations_valid ON quotations(valid_until)');
        }
        
        // Index untuk job_costs - status
        if (Schema::hasTable('job_costs') && !$this->indexExists('job_costs', 'idx_job_costs_status')) {
            DB::statement('CREATE INDEX idx_job_costs_status ON job_costs(status)');
        }
        
        // Index untuk job_costs - shipment_id
        if (Schema::hasTable('job_costs') && !$this->indexExists('job_costs', 'idx_job_costs_shipment')) {
            DB::statement('CREATE INDEX idx_job_costs_shipment ON job_costs(shipment_id)');
        }
        
        // Index untuk cash_transactions - transaction_date + type
        if (Schema::hasTable('cash_transactions') && !$this->indexExists('cash_transactions', 'idx_cash_date_type')) {
            DB::statement('CREATE INDEX idx_cash_date_type ON cash_transactions(transaction_date, type)');
        }
        
        // Index untuk activity_logs - created_at
        if (Schema::hasTable('activity_logs') && !$this->indexExists('activity_logs', 'idx_activity_created')) {
            DB::statement('CREATE INDEX idx_activity_created ON activity_logs(created_at)');
        }
        
        // Index untuk activity_logs - user_name
        if (Schema::hasTable('activity_logs') && !$this->indexExists('activity_logs', 'idx_activity_user')) {
            DB::statement('CREATE INDEX idx_activity_user ON activity_logs(user_name)');
        }
        
        // Index untuk customers - created_at (new customers this month)
        if (!$this->indexExists('customers', 'idx_customers_created')) {
            DB::statement('CREATE INDEX idx_customers_created ON customers(created_at)');
        }
        
        // Index untuk shipments - service_type
        if (!$this->indexExists('shipments', 'idx_shipments_service')) {
            DB::statement('CREATE INDEX idx_shipments_service ON shipments(service_type)');
        }
        
        // Index untuk shipments - lane_status
        if (Schema::hasColumn('shipments', 'lane_status') && !$this->indexExists('shipments', 'idx_shipments_lane')) {
            DB::statement('CREATE INDEX idx_shipments_lane ON shipments(lane_status)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_invoices_due_date ON invoices');
        DB::statement('DROP INDEX IF EXISTS idx_invoices_status_due ON invoices');
        DB::statement('DROP INDEX IF EXISTS idx_invoices_payment_date ON invoices');
        DB::statement('DROP INDEX IF EXISTS idx_quotations_status ON quotations');
        DB::statement('DROP INDEX IF EXISTS idx_quotations_valid ON quotations');
        DB::statement('DROP INDEX IF EXISTS idx_job_costs_status ON job_costs');
        DB::statement('DROP INDEX IF EXISTS idx_job_costs_shipment ON job_costs');
        DB::statement('DROP INDEX IF EXISTS idx_cash_date_type ON cash_transactions');
        DB::statement('DROP INDEX IF EXISTS idx_activity_created ON activity_logs');
        DB::statement('DROP INDEX IF EXISTS idx_activity_user ON activity_logs');
        DB::statement('DROP INDEX IF EXISTS idx_customers_created ON customers');
        DB::statement('DROP INDEX IF EXISTS idx_shipments_service ON shipments');
        DB::statement('DROP INDEX IF EXISTS idx_shipments_lane ON shipments');
    }
    
    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
