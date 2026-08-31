<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\CashTransaction;
use App\Models\Vendor;
use App\Models\Email;
use App\Models\HistoricalSnapshot;
use App\Support\CacheHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Dashboard extends Component
{
    public $period = 'month';
    public $startDate;
    public $endDate;
    public $showCustomRange = false;

    public function mount()
    {
        // Initialize default dates
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        
        // Check access - only admin and director
        $user = Auth::user();
        $allowedRoles = ['admin', 'director', 'super_admin'];
        
        if (!$user->hasRole($allowedRoles)) {
            // Redirect atau tampilkan dashboard terbatas
        }
    }

    public function updatedPeriod($value)
    {
        if ($value === "custom") {
            $this->showCustomRange = true;
        } else {
            $this->showCustomRange = false;
            $now = now();
            switch($value) {
                case "today":
                    $this->startDate = $now->format("Y-m-d");
                    $this->endDate = $now->format("Y-m-d");
                    break;
                case "week":
                    $this->startDate = $now->copy()->startOfWeek()->format("Y-m-d");
                    $this->endDate = $now->format("Y-m-d");
                    break;
                case "month":
                    $this->startDate = $now->copy()->startOfMonth()->format("Y-m-d");
                    $this->endDate = $now->format("Y-m-d");
                    break;
                case "year":
                    $this->startDate = $now->copy()->startOfYear()->format("Y-m-d");
                    $this->endDate = $now->format("Y-m-d");
                    break;
            }
        }
    }

    public function applyCustomRange()
    {
        if ($this->startDate && $this->endDate) {
            $this->period = "custom";
            $this->showCustomRange = true;
        }
    }

    public function getDateRange()
    {
        if ($this->period === "custom" && $this->startDate && $this->endDate) {
            return [
                "start" => \Carbon\Carbon::parse($this->startDate)->startOfDay(),
                "end" => \Carbon\Carbon::parse($this->endDate)->endOfDay()
            ];
        }
        
        $now = \Carbon\Carbon::now();
        return match($this->period) {
            "today" => ["start" => $now->copy()->startOfDay(), "end" => $now->copy()->endOfDay()],
            "week" => ["start" => $now->copy()->startOfWeek(), "end" => $now->copy()->endOfDay()],
            "month" => ["start" => $now->copy()->startOfMonth(), "end" => $now->copy()->endOfDay()],
            "year" => ["start" => $now->copy()->startOfYear(), "end" => $now->copy()->endOfDay()],
            default => ["start" => $now->copy()->startOfMonth(), "end" => $now->copy()->endOfDay()]
        };
    }

    public function getFormattedDateRange()
    {
        $range = $this->getDateRange();
        return $range["start"]->format("d M Y") . " - " . $range["end"]->format("d M Y");
    }

    /**
     * Nama periode pembanding, dipakai badge pertumbuhan.
     * Tanpa ini "↓97%" tidak menjelaskan dibanding apa.
     */
    public function getComparisonLabel(): string
    {
        return match ($this->period) {
            'today'  => 'vs kemarin',
            'week'   => 'vs minggu lalu',
            'year'   => 'vs tahun lalu',
            'custom' => 'vs periode sebelumnya',
            default  => 'vs bulan lalu',
        };
    }

    /**
     * Versi cache statistik. Dinaikkan CacheHelper::invalidateAdminStats()
     * setiap ada perubahan yang mempengaruhi angka dashboard, sehingga
     * angka tidak lagi tertinggal sampai TTL habis.
     */
    private function statsVersion(): int
    {
        return (int) Cache::get('admin_stats_version', 1);
    }

    /** Tombol "Perbarui": buang cache lalu render ulang dengan angka segar. */
    public function refreshStats(): void
    {
        CacheHelper::invalidateAdminStats();
        Cache::forget('dashboard_growth_chart');
        $this->dispatch('stats-refreshed');
    }

    /**
     * Ekspresi bulan yang jalan di MySQL maupun SQLite.
     *
     * MONTH() tidak ada di SQLite, dan itulah sebabnya seluruh halaman
     * dashboard sebelumnya tidak bisa diuji sama sekali — halaman yang
     * paling sering dibuka justru nol tes.
     */
    private function monthExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";
    }

    /** Ekspresi 'YYYY-MM' lintas driver (pengganti DATE_FORMAT). */
    private function yearMonthExpr(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /**
     * Angka kartu utama.
     *
     * Dipisah tegas antara ukuran ALIRAN (kejadian di dalam periode: shipment
     * baru, selesai, pendapatan, pelanggan baru) dan ukuran PERSEDIAAN
     * (keadaan saat ini: shipment aktif, total pelanggan & vendor). Sebelumnya
     * empat dari enam kartu memakai hitungan sepanjang waktu sementara
     * label kecilnya memakai periode — jadi memilih "Bulan Ini" seolah
     * mengubah seluruh halaman padahal hanya dua kartu yang ikut.
     */
    public function getMainStats()
    {
        $cacheKey = "dashboard_main_stats_v" . $this->statsVersion() . "_" . $this->period . "_" . $this->startDate . "_" . $this->endDate;

        return Cache::remember($cacheKey, 300, function () {
            $dateRange = $this->getDateRange();
            $startDate = $dateRange["start"];
            $endDate = $dateRange["end"];

            $currentShipments = Shipment::whereNotIn('status', ['cancel', 'cancelled'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $currentRevenue = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->sum('grand_total');

            $prevStart = match ($this->period) {
                'today' => $startDate->copy()->subDay(),
                'week'  => $startDate->copy()->subWeek(),
                'year'  => $startDate->copy()->subYear(),
                // Rentang custom dibandingkan dengan rentang sepanjang itu juga,
                // tepat sebelum periodenya.
                'custom' => $startDate->copy()->subDays($startDate->diffInDays($endDate) + 1),
                default => $startDate->copy()->subMonth(),
            };
            $prevEnd = $startDate->copy()->subDay()->endOfDay();

            $prevShipments = Shipment::whereNotIn('status', ['cancel', 'cancelled'])
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->count();
            $prevRevenue = Invoice::whereBetween('invoice_date', [$prevStart, $prevEnd])
                ->where('status', '!=', 'cancelled')
                ->sum('grand_total');

            return [
                // ── Aliran: ikut periode yang dipilih ───────────────────────
                'current_shipments'   => $currentShipments,
                'shipment_growth'     => $this->growthPercent($currentShipments, $prevShipments),
                'completed_period'    => Shipment::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])->count(),
                'current_revenue'     => $currentRevenue,
                'revenue_growth'      => $this->growthPercent($currentRevenue, $prevRevenue),
                // Batas atas sebelumnya tidak ada, sehingga rentang lampau ikut
                // menghitung pelanggan yang mendaftar sesudahnya.
                'new_customers'       => Customer::whereBetween('created_at', [$startDate, $endDate])->count(),

                // ── Persediaan: keadaan saat ini, tidak ada kaitan periode ──
                'total_shipments'     => Shipment::whereNotIn('status', ['cancel', 'cancelled'])->count(),
                'active_shipments'    => Shipment::whereIn('status', ['pending', 'document_collection', 'in_progress', 'in_transit'])->count(),
                'completed_shipments' => Shipment::where('status', 'completed')->count(),
                'total_customers'     => Customer::whereHas('user', fn ($q) => $q->where('role', 'customer'))->count(),
                'total_vendors'       => Vendor::count(),
            ];
        });
    }

    /**
     * Persentase pertumbuhan, atau null bila tidak ada pembanding.
     *
     * Sebelumnya pembanding nol menghasilkan 0 — terbaca "stagnan", padahal
     * artinya "dari tidak ada jadi ada". null membuat tampilan bisa menulis
     * "baru" alih-alih angka yang menyesatkan.
     */
    private function growthPercent(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function getFinancialStats()
    {
        return Cache::remember("dashboard_financial_stats_v" . $this->statsVersion(), 300, function () {
            return [
            'unpaid_invoices' => Invoice::where('status', 'unpaid')->count(),
            'unpaid_amount' => Invoice::where('status', 'unpaid')->sum('grand_total'),
            'overdue_invoices' => Invoice::where('status', 'unpaid')->where('due_date', '<', now())->count(),
            'overdue_amount' => Invoice::where('status', 'unpaid')->where('due_date', '<', now())->sum('grand_total'),
            'paid_this_month' => Invoice::where('status', 'paid')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('grand_total'),
            'cash_in_today' => CashTransaction::whereDate('transaction_date', today())->where('type', 'in')->sum('amount'),
            'cash_out_today' => CashTransaction::whereDate('transaction_date', today())->where('type', 'out')->sum('amount'),
            ];
        });
    }

    /**
     * "Perlu tindakan hari ini".
     *
     * Dashboard sebelumnya melaporkan jumlah, bukan pekerjaan. Daftar ini
     * menjawab pertanyaan yang sebenarnya dibuka tiap pagi: apa yang harus
     * dikerjakan lebih dulu. Urutannya dari yang paling merugikan kalau
     * didiamkan — ETA terlewat memicu telepon customer, invoice jatuh tempo
     * menahan uang masuk, job costing kosong bikin margin tak terhitung.
     */
    public function getActionItems(): array
    {
        return Cache::remember('dashboard_action_items_v' . $this->statsVersion(), 60, function () {
            $items = [];
            $berjalan = ['pending', 'document_collection', 'in_progress', 'in_transit'];

            $etaTerlewat = Shipment::whereIn('status', $berjalan)
                ->whereNotNull('estimated_arrival')
                ->whereDate('estimated_arrival', '<', today())
                ->count();
            if ($etaTerlewat > 0) {
                $items[] = [
                    'level' => 'danger', 'icon' => '⏰', 'count' => $etaTerlewat,
                    'title' => 'Shipment lewat estimasi',
                    'message' => 'Tanggal perkiraan sudah lewat tapi statusnya masih berjalan',
                    'link' => route('admin.shipments.index'),
                ];
            }

            $overdueCount = Invoice::where('status', 'unpaid')->where('due_date', '<', now())->count();
            if ($overdueCount > 0) {
                $items[] = [
                    'level' => 'danger', 'icon' => '🧾', 'count' => $overdueCount,
                    'title' => 'Invoice lewat jatuh tempo',
                    'message' => 'Uang masuk tertahan sampai ditagih ulang',
                    'link' => route('admin.invoices.index'),
                ];
            }

            // Tanpa dokumen sama sekali padahal barang segera tiba — ini yang
            // berujung barang menumpuk di pelabuhan.
            $dokumenKosong = Shipment::whereIn('status', $berjalan)
                ->whereNotNull('estimated_arrival')
                ->whereDate('estimated_arrival', '<=', today()->addDays(3))
                ->whereDoesntHave('documents')
                ->count();
            if ($dokumenKosong > 0) {
                $items[] = [
                    'level' => 'warning', 'icon' => '📄', 'count' => $dokumenKosong,
                    'title' => 'Belum ada dokumen, ETA ≤3 hari',
                    'message' => 'Barang segera tiba tapi belum satu pun dokumen diunggah',
                    'link' => route('admin.shipments.index'),
                ];
            }

            $pendingOld = Shipment::where('status', 'pending')->where('created_at', '<', now()->subDays(3))->count();
            if ($pendingOld > 0) {
                $items[] = [
                    'level' => 'warning', 'icon' => '🕒', 'count' => $pendingOld,
                    'title' => 'Pending lebih dari 3 hari',
                    'message' => 'Shipment belum bergerak dari status awal',
                    'link' => route('admin.shipments.index'),
                ];
            }

            // Selesai tapi biayanya tidak pernah dicatat = laba tidak terhitung.
            $tanpaJobCost = Shipment::where('status', 'completed')
                ->whereDoesntHave('jobCosts')
                ->count();
            if ($tanpaJobCost > 0) {
                $items[] = [
                    'level' => 'warning', 'icon' => '💼', 'count' => $tanpaJobCost,
                    'title' => 'Selesai tanpa job costing',
                    'message' => 'Labanya belum bisa dihitung sampai biayanya dicatat',
                    'link' => route('admin.job-costing.index'),
                ];
            }

            $pendingProofs = Invoice::where('status', 'unpaid')->whereNotNull('payment_proof')->count();
            if ($pendingProofs > 0) {
                $items[] = [
                    'level' => 'info', 'icon' => '💳', 'count' => $pendingProofs,
                    'title' => 'Bukti bayar menunggu verifikasi',
                    'message' => 'Customer sudah bayar, tinggal dicocokkan',
                    'link' => route('admin.invoices.index'),
                ];
            }

            $unreadEmails = Email::where('is_read', false)->count();
            if ($unreadEmails > 0) {
                $items[] = [
                    'level' => 'info', 'icon' => '📬', 'count' => $unreadEmails,
                    'title' => 'Email belum dibaca',
                    'message' => 'Ada email masuk yang belum diproses',
                    'link' => route('inbox.index'),
                ];
            }

            return $items;
        });
    }


    /**
     * Grafik bulanan tahun berjalan.
     *
     * Berhenti di bulan ini — sebelumnya Sep–Des ikut digambar nol sehingga
     * grafik terbaca seolah pendapatan anjlok, padahal bulannya memang belum
     * datang. Median ikut dikirim sebagai garis pembanding karena satu bulan
     * yang melonjak membuat bulan lain rata di dasar grafik.
     */
    public function getMonthlyChartData()
    {
        $year = now()->year;
        $bulanTerakhir = now()->month;
        $awal = Carbon::create($year, 1, 1)->startOfDay();
        $akhir = now()->endOfMonth();

        $shipments = Shipment::whereNotIn('status', ['cancel', 'cancelled'])
            ->whereBetween('created_at', [$awal, $akhir])
            ->selectRaw($this->monthExpr('created_at') . ' as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $revenue = Invoice::whereBetween('invoice_date', [$awal, $akhir])
            ->where('status', '!=', 'cancelled')
            ->selectRaw($this->monthExpr('invoice_date') . ' as month, SUM(grand_total) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $labels = [];
        $shipmentData = [];
        $revenueData = [];
        for ($i = 1; $i <= $bulanTerakhir; $i++) {
            $labels[] = Carbon::create($year, $i, 1)->translatedFormat('M');
            $shipmentData[] = (int) ($shipments[$i] ?? 0);
            $revenueData[] = (float) ($revenue[$i] ?? 0);
        }

        $terisi = array_values(array_filter($revenueData, fn ($v) => $v > 0));
        sort($terisi);
        $median = $terisi ? $terisi[intdiv(count($terisi), 2)] : 0;

        return [
            'labels' => $labels,
            'shipments' => $shipmentData,
            'revenue' => $revenueData,
            'revenue_median' => $median,
        ];
    }

    /**
     * Lima customer teratas berdasarkan NILAI, bukan jumlah job.
     *
     * Sembilan job LCL kecil bukan customer terbesar; yang menentukan bagi
     * forwarder adalah rupiah yang masuk. Jumlah job tetap ditampilkan
     * sebagai konteks.
     */
    public function getTopCustomers()
    {
        $range = $this->getDateRange();

        return Cache::remember(
            'dashboard_top_customers_v' . $this->statsVersion() . '_' . $this->period . '_' . $this->startDate . '_' . $this->endDate,
            300,
            fn () => Customer::query()
                ->withCount(['shipments' => fn ($q) => $q->whereNotIn('status', ['cancel', 'cancelled'])->whereBetween('created_at', [$range['start'], $range['end']])])
                ->withSum(
                    ['invoices' => fn ($q) => $q->where('status', '!=', 'cancelled')
                        ->whereBetween('invoice_date', [$range['start'], $range['end']])],
                    'grand_total'
                )
                ->get()
                ->filter(fn ($c) => ($c->invoices_sum_grand_total ?? 0) > 0 || $c->shipments_count > 0)
                ->sortByDesc(fn ($c) => (float) ($c->invoices_sum_grand_total ?? 0))
                ->take(5)
                ->values()
        );
    }

    public function getRecentShipments()
    {
        return Shipment::with('customer')->latest()->take(5)->get();
    }

    /**
     * Corong pipeline shipment yang masih berjalan.
     *
     * Donut lama menghitung semua status sepanjang waktu, dan karena 70 dari
     * 80 sudah "completed", 87% lingkarannya hijau — cantik tapi tidak bisa
     * ditindaklanjuti. Yang dikerjakan kepala operasional adalah yang belum
     * selesai, jadi itu yang ditampilkan, berurut sesuai alur kerja.
     */
    public function getPipeline(): array
    {
        return Cache::remember('dashboard_pipeline_v' . $this->statsVersion(), 300, function () {
            // Warna mengikuti arti status yang sudah dipakai di seluruh portal
            // (kuning menunggu, indigo dokumen, biru proses, ungu jalan), tapi
            // dipilih langkah yang lebih pekat supaya keempatnya tetap terbedakan
            // oleh mata yang buta warna — sudah diuji dengan validator palet.
            $urutan = [
                'pending'             => ['label' => 'Menunggu',   'color' => '#d97706'],
                'document_collection' => ['label' => 'Dokumen',    'color' => '#4f46e5'],
                'in_progress'         => ['label' => 'Proses',     'color' => '#0891b2'],
                'in_transit'          => ['label' => 'Perjalanan', 'color' => '#9333ea'],
            ];

            $counts = Shipment::whereIn('status', array_keys($urutan))
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $total = array_sum($counts);
            $tahap = [];
            foreach ($urutan as $status => $meta) {
                $jumlah = (int) ($counts[$status] ?? 0);
                $tahap[] = [
                    'status'  => $status,
                    'label'   => $meta['label'],
                    'color'   => $meta['color'],
                    'count'   => $jumlah,
                    'percent' => $total > 0 ? round($jumlah / $total * 100) : 0,
                ];
            }

            return [
                'stages'    => $tahap,
                'total'     => $total,
                'completed' => Shipment::where('status', 'completed')->count(),
            ];
        });
    }

    /**
     * Gabungkan data historis Accurate (2024-2025) + data live portal (2026-)
     * untuk chart pertumbuhan multi-tahun.
     */
    public function getGrowthChartData(): array
    {
        return Cache::remember('dashboard_growth_chart_v' . $this->statsVersion(), 600, function () {
            $labels   = [];
            $revenue  = [];
            $gross    = [];

            // ── Data bulanan dari Accurate: 2024 & 2025 ──────────────────────
            $historical = HistoricalSnapshot::where('period_type', 'monthly')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            $periods = [];
            foreach (['2024', '2025'] as $year) {
                for ($m = 1; $m <= 12; $m++) {
                    $periods[] = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                }
            }

            foreach ($periods as $p) {
                $snap = $historical->get($p);
                $labels[]  = \Carbon\Carbon::createFromFormat('Y-m', $p)->format('M Y');
                $revenue[] = $snap ? (float) $snap->revenue      : 0;
                $gross[]   = $snap ? (float) $snap->gross_profit : 0;
            }

            // ── Data live dari portal: 2026 s.d sekarang ─────────────────────
            $portalRevenue = Invoice::where('invoice_date', '>=', '2026-01-01')
                ->where('status', '!=', 'cancelled')
                ->selectRaw($this->yearMonthExpr('invoice_date') . " as period, SUM(grand_total) as total")
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('total', 'period')
                ->toArray();

            // Pastikan semua bulan 2026 s.d. bulan ini ada
            $start = \Carbon\Carbon::create(2026, 1, 1);
            $end   = now()->startOfMonth();
            while ($start->lte($end)) {
                $p = $start->format('Y-m');
                $labels[]  = $start->format('M Y') . ' *';
                $revenue[] = (float) ($portalRevenue[$p] ?? 0);
                $gross[]   = 0; // portal belum punya gross profit per bulan
                $start->addMonth();
            }

            // ── Summary tahunan untuk card ────────────────────────────────────
            $annualHistorical = HistoricalSnapshot::where('period_type', 'annual')
                ->orderBy('period')
                ->get();

            $annualSummary = [];
            foreach ($annualHistorical as $snap) {
                $annualSummary[$snap->period] = [
                    'revenue'      => $snap->revenue,
                    'gross_profit' => $snap->gross_profit,
                    'net_profit'   => $snap->net_profit,
                ];
            }
            // Tambah 2024, 2025 dari monthly sum
            foreach (['2024', '2025'] as $year) {
                $rows = HistoricalSnapshot::where('period_type', 'monthly')
                    ->where('period', 'like', $year . '-%')
                    ->get();
                if ($rows->isNotEmpty()) {
                    $annualSummary[$year] = [
                        'revenue'      => $rows->sum('revenue'),
                        'gross_profit' => $rows->sum('gross_profit'),
                        'net_profit'   => $rows->sum('net_profit'),
                    ];
                }
            }
            // 2026 dari portal
            $rev2026 = Invoice::where('invoice_date', '>=', '2026-01-01')
                ->where('status', '!=', 'cancelled')
                ->sum('grand_total');
            if ($rev2026 > 0) {
                $annualSummary['2026'] = ['revenue' => $rev2026, 'gross_profit' => 0, 'net_profit' => 0];
            }
            ksort($annualSummary);

            return [
                'labels'        => $labels,
                'revenue'       => $revenue,
                'gross'         => $gross,
                'annualSummary' => $annualSummary,
            ];
        });
    }

    /**
     * Laba bersih: angka terpenting di halaman ini, sebelumnya tercetak 11px
     * di pojok kartu tahun. Rugi yang menyusut konsisten adalah kabar baik
     * dan layak dibaca sekali lihat, bukan dicari.
     */
    public function getNetProfitTrend(): ?array
    {
        $tahunan = collect($this->getGrowthChartData()['annualSummary'] ?? [])
            ->filter(fn ($d) => (float) ($d['net_profit'] ?? 0) != 0.0);

        if ($tahunan->isEmpty()) {
            return null;
        }

        $tahunTerakhir = $tahunan->keys()->last();
        $terakhir = (float) $tahunan[$tahunTerakhir]['net_profit'];
        $sebelumnya = $tahunan->count() > 1
            ? (float) $tahunan->values()[$tahunan->count() - 2]['net_profit']
            : null;

        return [
            'year'      => $tahunTerakhir,
            'value'     => $terakhir,
            'previous'  => $sebelumnya,
            // Membaik = mendekati/melewati nol dari arah rugi.
            'improving' => $sebelumnya !== null && $terakhir > $sebelumnya,
            'series'    => $tahunan->map(fn ($d, $y) => ['year' => $y, 'value' => (float) $d['net_profit']])
                ->values()->take(-5)->all(),
        ];
    }

    public function getTodayStats()
    {
        return [
            'shipments_today' => Shipment::whereNotIn('status', ['cancel', 'cancelled'])->whereDate('created_at', today())->count(),
            'invoices_today' => Invoice::whereDate('invoice_date', today())->count(),
            'payments_today' => Invoice::whereDate('payment_date', today())->where('status', 'paid')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'mainStats' => $this->getMainStats(),
            'financialStats' => $this->getFinancialStats(),
            'actionItems' => $this->getActionItems(),
            'chartData' => $this->getMonthlyChartData(),
            'topCustomers' => $this->getTopCustomers(),
            'recentShipments' => $this->getRecentShipments(),
            'pipeline' => $this->getPipeline(),
            'todayStats' => $this->getTodayStats(),
            'growthData' => $this->getGrowthChartData(),
            'netProfit' => $this->getNetProfitTrend(),
            'comparisonLabel' => $this->getComparisonLabel(),
        ])->layout('layouts.admin');
    }
}