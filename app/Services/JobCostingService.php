<?php

namespace App\Services;

use App\Models\JobCost;
use App\Models\CashTransaction;
use App\Models\Shipment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class JobCostingService
{
    /**
     * Record cost from cash transaction
     */
    public function recordCostFromCashTransaction(CashTransaction $cashTransaction)
    {
        if (!$cashTransaction->shipment_id || $cashTransaction->cost_category !== 'shipment') {
            return null;
        }
        
        // Create job cost entry
        $jobCost = JobCost::create([
            'shipment_id' => $cashTransaction->shipment_id,
            'vendor_id' => $cashTransaction->vendor_id,
            'cost_type' => $this->determineCostType($cashTransaction),
            'description' => $cashTransaction->description,
            'amount' => $cashTransaction->amount_idr,
            'currency' => $cashTransaction->currency,
            'exchange_rate' => $cashTransaction->exchange_rate,
            'cost_date' => $cashTransaction->transaction_date,
            'reference_type' => 'cash_transaction',
            'reference_id' => $cashTransaction->id,
            'created_by' => Auth::id(),
        ]);
        
        // Update shipment profit calculation
        $this->updateShipmentProfit($cashTransaction->shipment_id);
        
        return $jobCost;
    }
    
    /**
     * Determine cost type from transaction description
     */
    private function determineCostType($cashTransaction)
    {
        $description = strtolower($cashTransaction->description);
        
        if (str_contains($description, 'freight') || str_contains($description, 'ocean') || str_contains($description, 'air')) {
            return 'freight';
        }
        
        if (str_contains($description, 'truck') || str_contains($description, 'transport')) {
            return 'trucking';
        }
        
        if (str_contains($description, 'customs') || str_contains($description, 'bea cukai') || str_contains($description, 'clearance')) {
            return 'customs';
        }
        
        if (str_contains($description, 'document') || str_contains($description, 'dokumen')) {
            return 'documentation';
        }
        
        if (str_contains($description, 'handling')) {
            return 'handling';
        }
        
        if (str_contains($description, 'insurance') || str_contains($description, 'asuransi')) {
            return 'insurance';
        }
        
        return 'other';
    }
    
    /**
     * Calculate and update shipment profit
     */
    public function updateShipmentProfit($shipmentId)
    {
        $shipment = Shipment::find($shipmentId);
        
        if (!$shipment) {
            return null;
        }
        
        // Get total revenue from invoices
        $totalRevenue = $shipment->invoices()->sum('total_amount') ?: 0;
        
        // Get total costs from job costing
        $totalCost = $shipment->jobCosts()->sum('amount') ?: 0;
        
        // Calculate profit
        $grossProfit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
        
        return [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'profit_margin' => $profitMargin,
        ];
    }
    
    /**
     * Get shipment profitability summary
     */
    public function getShipmentProfitability($shipmentId)
    {
        $shipment = Shipment::with(['invoices', 'jobCosts.vendor'])->find($shipmentId);
        
        if (!$shipment) {
            return null;
        }
        
        $profitData = $this->updateShipmentProfit($shipmentId);
        
        // Get cost breakdown by type
        $costBreakdown = $shipment->jobCosts()
            ->selectRaw('cost_type, SUM(amount) as total')
            ->groupBy('cost_type')
            ->get()
            ->pluck('total', 'cost_type');
        
        return [
            'shipment' => $shipment,
            'revenue' => $profitData['total_revenue'],
            'total_cost' => $profitData['total_cost'],
            'profit' => $profitData['gross_profit'],
            'margin' => $profitData['profit_margin'],
            'cost_breakdown' => $costBreakdown,
            'cost_items' => $shipment->jobCosts,
        ];
    }
}
