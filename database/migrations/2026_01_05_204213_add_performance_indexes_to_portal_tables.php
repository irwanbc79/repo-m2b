<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes using raw SQL to avoid duplicates
        DB::statement('CREATE INDEX IF NOT EXISTS idx_shipments_status ON shipments(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_shipments_created ON shipments(created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_shipments_customer_status ON shipments(customer_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_shipments_awb ON shipments(awb_number)');
        
        DB::statement('CREATE INDEX IF NOT EXISTS idx_documents_shipment ON documents(shipment_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_documents_created ON documents(created_at)');
        
        DB::statement('CREATE INDEX IF NOT EXISTS idx_field_photos_created ON field_photos(created_at)');
        
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_shipment ON invoices(shipment_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_date ON invoices(invoice_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_customer_status ON invoices(customer_id, status)');
        
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_invoice ON invoice_payments(invoice_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_date ON invoice_payments(payment_date)');
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_verified ON invoice_payments(is_verified)');
        } catch (\Exception $e) {
            // Ignore if column does not exist
        }
        
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_job_costs_invoice ON job_costs(invoice_id)');
        } catch (\Exception $e) {
            // Ignore if column does not exist
        }
        DB::statement('CREATE INDEX IF NOT EXISTS idx_job_costs_vendor ON job_costs(vendor_id)');
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_job_costs_date ON job_costs(cost_date)');
        } catch (\Exception $e) {
            // Ignore if column does not exist
        }
        
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name)');
        } catch (\Exception $e) {
            // Ignore if column does not exist
        }
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email)');
        } catch (\Exception $e) {
            // Ignore if column does not exist
        }
        
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_shipments_status ON shipments');
        DB::statement('DROP INDEX IF EXISTS idx_shipments_created ON shipments');
        DB::statement('DROP INDEX IF EXISTS idx_shipments_customer_status ON shipments');
        DB::statement('DROP INDEX IF EXISTS idx_shipments_awb ON shipments');
        
        DB::statement('DROP INDEX IF EXISTS idx_documents_shipment ON documents');
        DB::statement('DROP INDEX IF EXISTS idx_documents_created ON documents');
        
        DB::statement('DROP INDEX IF EXISTS idx_field_photos_created ON field_photos');
        
        DB::statement('DROP INDEX IF EXISTS idx_invoices_shipment ON invoices');
        DB::statement('DROP INDEX IF EXISTS idx_invoices_status ON invoices');
        DB::statement('DROP INDEX IF EXISTS idx_invoices_date ON invoices');
        DB::statement('DROP INDEX IF EXISTS idx_invoices_customer_status ON invoices');
        
        DB::statement('DROP INDEX IF EXISTS idx_payments_invoice ON invoice_payments');
        DB::statement('DROP INDEX IF EXISTS idx_payments_date ON invoice_payments');
        try {
            DB::statement('DROP INDEX IF EXISTS idx_payments_verified ON invoice_payments');
        } catch (\Exception $e) {
            // Ignore
        }
        
        try {
            DB::statement('DROP INDEX IF EXISTS idx_job_costs_invoice ON job_costs');
        } catch (\Exception $e) {
            // Ignore
        }
        DB::statement('DROP INDEX IF EXISTS idx_job_costs_vendor ON job_costs');
        try {
            DB::statement('DROP INDEX IF EXISTS idx_job_costs_date ON job_costs');
        } catch (\Exception $e) {
            // Ignore
        }
        
        try {
            DB::statement('DROP INDEX IF EXISTS idx_customers_name ON customers');
        } catch (\Exception $e) {
            // Ignore
        }
        try {
            DB::statement('DROP INDEX IF EXISTS idx_customers_email ON customers');
        } catch (\Exception $e) {
            // Ignore
        }
        
        DB::statement('DROP INDEX IF EXISTS idx_users_role ON users');
    }
};
