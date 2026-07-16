<?php

namespace App\Livewire\Admin;

use App\Models\Shipment;
use Livewire\Component;

/**
 * Laporan Laba per Shipment — pendapatan (invoice) vs biaya (job cost) per
 * pengiriman, dengan laba kotor & margin. Untuk visibilitas profitabilitas
 * owner (freight forwarding hidup dari margin per shipment).
 *
 * Catatan freight forwarding: "Pendapatan" (grand_total) memuat reimbursement/
 * disbursement (pass-through) + jasa. Laba kotor (Pendapatan − Biaya) sudah
 * benar sbg angka absolut; kolom "Jasa" ditampilkan agar terlihat porsi fee riil.
 */
class ProfitReport extends Component
{
    public string $search = '';
    public string $marginFilter = 'all'; // all|loss|thin|healthy|unbilled
    public string $sort = 'margin_asc';  // margin_asc|margin_desc|profit_desc|revenue_desc

    public function render()
    {
        $items = Shipment::query()
            ->with('customer')
            ->withSum('invoices as revenue', 'grand_total')
            ->withSum('invoices as service_revenue', 'service_total')
            ->withSum('jobCosts as total_cost', 'amount')
            ->active()
            ->when($this->search, function ($q) {
                $q->where(function ($w) {
                    $w->where('awb_number', 'like', "%{$this->search}%")
                        ->orWhere('bl_number', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('company_name', 'like', "%{$this->search}%"));
                });
            })
            ->get()
            ->map(function ($s) {
                $rev = (float) ($s->revenue ?? 0);
                $cost = (float) ($s->total_cost ?? 0);
                $s->gross_profit = $rev - $cost;
                $s->margin_pct = $rev > 0 ? ($s->gross_profit / $rev * 100) : null;
                return $s;
            });

        $items = $items->filter(function ($s) {
            $rev = (float) ($s->revenue ?? 0);
            $cost = (float) ($s->total_cost ?? 0);
            return match ($this->marginFilter) {
                'loss'     => $rev > 0 && $s->gross_profit < 0,
                'thin'     => $s->margin_pct !== null && $s->margin_pct >= 0 && $s->margin_pct < 10,
                'healthy'  => $s->margin_pct !== null && $s->margin_pct >= 20,
                'unbilled' => $rev == 0 && $cost > 0,
                default    => true,
            };
        });

        $items = match ($this->sort) {
            'margin_desc'  => $items->sortByDesc(fn ($s) => $s->margin_pct ?? -9999),
            'profit_desc'  => $items->sortByDesc('gross_profit'),
            'revenue_desc' => $items->sortByDesc(fn ($s) => (float) ($s->revenue ?? 0)),
            default        => $items->sortBy(fn ($s) => $s->margin_pct ?? 9999), // margin_asc: terburuk dulu
        };

        $totRev = $items->sum(fn ($s) => (float) ($s->revenue ?? 0));
        $totCost = $items->sum(fn ($s) => (float) ($s->total_cost ?? 0));
        $totProfit = $totRev - $totCost;

        return view('livewire.admin.profit-report', [
            'shipments' => $items->values(),
            'summary' => [
                'count'   => $items->count(),
                'revenue' => $totRev,
                'cost'    => $totCost,
                'profit'  => $totProfit,
                'margin'  => $totRev > 0 ? ($totProfit / $totRev * 100) : 0,
            ],
        ])->layout('layouts.admin');
    }
}
