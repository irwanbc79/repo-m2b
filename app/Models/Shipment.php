<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'quotation_id',
        'parent_shipment_id',
        'awb_number',
        'bl_number',
        'shipment_type',
        'container_mode',
        'container_info',
        'service_type',
        'customs_regime',
        'status',
        'lane_status',
        'origin',
        'destination',
        'shipper_name',
        'consignee_name',
        'weight',
        'volume',
        'pieces',
        'package_type',
        'commodity',
        'hs_code',
        'estimated_departure',
        'estimated_arrival',
        'actual_departure',
        'actual_arrival',
        'customs_deadline_at',
        'customs_reference_number',
        'customs_regime_notes',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'do_containers',
        'do_alamat_bongkar',
        'do_nama_penerima',
    ];

    protected $casts = [
        'do_containers' => 'array',
        'cancelled_at' => 'datetime',
        'estimated_departure' => 'datetime',
        'estimated_arrival' => 'datetime',
        'actual_departure' => 'datetime',
        'actual_arrival' => 'datetime',
        'customs_deadline_at' => 'date',
        'weight' => 'decimal:2',
        'volume' => 'decimal:2',
        'pieces' => 'integer',
    ];

    // =========================================
    // STATUS CONSTANTS
    // =========================================
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_CANCEL = 'cancelled'; // Alias untuk backward compatibility
    const STATUS_RE_EXPORT = 're_export';

    // =========================================
    // CUSTOMS REGIME CONSTANTS (LINKED DOSSIER)
    // =========================================
    const REGIME_REGULAR = 'regular';
    const REGIME_TEMPORARY_IMPORT = 'temporary_import';
    const REGIME_TEMPORARY_EXPORT = 'temporary_export';
    const REGIME_RE_EXPORT_REJECTION = 're_export_rejection';
    const REGIME_RE_EXPORT_TEMP = 're_export_temp';
    const REGIME_RE_IMPORT_RETURN = 're_import_return';
    const REGIME_RE_IMPORT_TEMP = 're_import_temp';

    // =========================================
    // STATUS PER SERVICE TYPE
    // =========================================
    
    // IMPORT STATUSES
    const STATUS_IMPORT_BOOKING = 'booking';
    const STATUS_IMPORT_DOCUMENT = 'document_collection';
    const STATUS_IMPORT_MANIFEST = 'manifest_submitted';
    const STATUS_IMPORT_BILLING = 'billing_issued';
    const STATUS_IMPORT_INSPECTION = 'physical_inspection'; // Jalur Merah
    const STATUS_IMPORT_RELEASED = 'customs_released';
    const STATUS_IMPORT_DELIVERY = 'delivery';
    
    // EXPORT STATUSES
    const STATUS_EXPORT_BOOKING = 'booking';
    const STATUS_EXPORT_DOCUMENT = 'document_collection';
    const STATUS_EXPORT_PEB = 'peb_submitted';
    const STATUS_EXPORT_BILLING = 'billing_issued'; // Jika ada Bea Keluar
    const STATUS_EXPORT_INSPECTION = 'physical_inspection'; // PPB
    const STATUS_EXPORT_RELEASED = 'export_released';
    const STATUS_EXPORT_ONBOARD = 'on_board';
    
    // DOMESTIC STATUSES
    const STATUS_DOMESTIC_BOOKING = 'booking';
    const STATUS_DOMESTIC_PICKUP = 'pickup';
    const STATUS_DOMESTIC_TRANSIT = 'in_transit';
    const STATUS_DOMESTIC_DELIVERY = 'delivery';
    
    // LANE STATUS
    const LANE_GREEN = 'green';
    const LANE_RED = 'red';

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => '⏳ Pending',
            self::STATUS_IN_PROGRESS => '🔄 In Progress',
            self::STATUS_IN_TRANSIT => '🚚 In Transit',
            self::STATUS_COMPLETED => '✅ Completed',
            self::STATUS_CANCELLED => '❌ Cancelled',
        ];
    }

    /**
     * Get status badge HTML
     */

    /**
     * Get status flow berdasarkan service type
     */
    public static function getStatusFlow(string $serviceType): array
    {
        return match(strtolower($serviceType)) {
            'import' => [
                'booking' => ['order' => 1, 'label' => 'Booking', 'icon' => '📋'],
                'document_collection' => ['order' => 2, 'label' => 'Document Collection', 'icon' => '📄'],
                'manifest_submitted' => ['order' => 3, 'label' => 'Manifest Submitted', 'icon' => '📝'],
                'billing_issued' => ['order' => 4, 'label' => 'Billing Issued', 'icon' => '💰'],
                'physical_inspection' => ['order' => 4.5, 'label' => 'Physical Inspection', 'icon' => '🔍', 'optional' => true],
                'customs_released' => ['order' => 5, 'label' => 'Customs Released', 'icon' => '✅'],
                'delivery' => ['order' => 6, 'label' => 'Delivery', 'icon' => '🚚'],
                'completed' => ['order' => 7, 'label' => 'Completed', 'icon' => '🎉'],
            ],
            'export' => [
                'booking' => ['order' => 1, 'label' => 'Booking', 'icon' => '📋'],
                'document_collection' => ['order' => 2, 'label' => 'Document Collection', 'icon' => '📄'],
                'peb_submitted' => ['order' => 3, 'label' => 'PEB Submitted', 'icon' => '📝'],
                'billing_issued' => ['order' => 3.5, 'label' => 'Billing (Bea Keluar)', 'icon' => '💰', 'optional' => true],
                'physical_inspection' => ['order' => 3.7, 'label' => 'PPB (Pemeriksaan)', 'icon' => '🔍', 'optional' => true],
                'export_released' => ['order' => 4, 'label' => 'NPE Released', 'icon' => '✅'],
                'on_board' => ['order' => 5, 'label' => 'On Board', 'icon' => '🚢'],
                'completed' => ['order' => 6, 'label' => 'Completed', 'icon' => '🎉'],
            ],
            default => [ // Domestic
                'booking' => ['order' => 1, 'label' => 'Booking', 'icon' => '📋'],
                'pickup' => ['order' => 2, 'label' => 'Pickup', 'icon' => '📦'],
                'in_transit' => ['order' => 3, 'label' => 'In Transit', 'icon' => '🚚'],
                'delivery' => ['order' => 4, 'label' => 'Delivery', 'icon' => '🏠'],
                'completed' => ['order' => 5, 'label' => 'Completed', 'icon' => '🎉'],
            ],
        };
    }

    /**
     * Get document triggers untuk auto-update status
     */
    public static function getDocumentTriggers(string $serviceType): array
    {
        return match(strtolower($serviceType)) {
            'import' => [
                'Bill of Lading' => 'document_collection',
                'Invoice' => 'document_collection',
                'Packing List' => 'document_collection',
                'Manifest / BC 1.1' => 'manifest_submitted',
                'Billing Pungutan' => 'billing_issued',
                'SPJM' => 'physical_inspection',
                'SPPB' => 'customs_released',
                'SP2' => 'delivery',
            ],
            'export' => [
                'Invoice' => 'document_collection',
                'Packing List' => 'document_collection',
                'BC 3.0' => 'peb_submitted',
                'Billing Bea Keluar' => 'billing_issued',
                'PPB' => 'physical_inspection',
                'NPE' => 'export_released',
                'Bill of Lading' => 'on_board',
            ],
            default => [ // Domestic
                'Surat Jalan Pickup' => 'pickup',
                'Manifest' => 'in_transit',
                'Surat Jalan' => 'delivery',
                'Bukti Terima' => 'completed',
            ],
        };
    }

    /**
     * Milestone TERJAUH berdasarkan dokumen yang ada (single source of truth).
     * Dokumen menyimpan tipe di kolom `description` (prefix), dicocokkan dgn
     * getDocumentTriggers. Return kunci status (mis. 'billing_issued') atau null.
     */
    public function computeMilestoneFromDocuments(): ?string
    {
        $triggers = self::getDocumentTriggers($this->service_type);
        $flow = self::getStatusFlow($this->service_type);
        $descs = ($this->relationLoaded('documents') ? $this->documents : $this->documents()->get())
            ->pluck('description')->filter();

        $best = null;
        $bestOrder = 0.0;
        foreach ($triggers as $docType => $statusKey) {
            if ($descs->contains(fn ($d) => stripos($d, $docType) === 0)) {
                $order = (float) ($flow[$statusKey]['order'] ?? 0);
                if ($order > $bestOrder) {
                    $bestOrder = $order;
                    $best = $statusKey;
                }
            }
        }

        return $best;
    }

    /**
     * Deteksi jalur (lane_status) otomatis berdasarkan dokumen yang ada:
     * - Jalur Merah (red): SPJM (import) atau PPB (export)
     * - Jalur Hijau (green): NPE, Billing Pungutan, Billing Bea Keluar, atau SPPB
     */
    public function computeLaneStatusFromDocuments(): ?string
    {
        $descs = ($this->relationLoaded('documents') ? $this->documents : $this->documents()->get())
            ->pluck('description')->filter();

        // SPJM (impor) atau PPB (ekspor) => Jalur Merah
        if ($descs->contains(fn ($d) => stripos($d, 'SPJM') === 0 || stripos($d, 'PPB') === 0)) {
            return 'red';
        }

        // NPE, Billing Pungutan, Billing Bea Keluar, SPPB => Jalur Hijau
        if ($descs->contains(fn ($d) => 
            stripos($d, 'NPE') === 0 || 
            stripos($d, 'Billing Pungutan') === 0 || 
            stripos($d, 'Billing Bea Keluar') === 0 || 
            stripos($d, 'SPPB') === 0
        )) {
            return 'green';
        }

        return null;
    }

    /**
     * Ambil lane status efektif (dari database, atau dihitung otomatis dari dokumen)
     */
    public function getEffectiveLaneStatusAttribute(): ?string
    {
        return $this->lane_status ?: $this->computeLaneStatusFromDocuments();
    }

    /**
     * Urutan (order) sebuah status untuk perbandingan maju/mundur.
     * Mendukung alias operasional (pending/in_progress/dll) di luar flow.
     */
    public function statusOrder(?string $status = null): float
    {
        $status = $status ?? $this->status;
        $flow = self::getStatusFlow($this->service_type);
        if (isset($flow[$status])) {
            return (float) ($flow[$status]['order'] ?? 0);
        }

        return match ($status) {
            'pending'             => 1.0,
            'in_progress'         => 2.0,
            'in_transit'          => 3.0,
            'completed'           => 999.0,
            'cancel', 'cancelled' => -1.0,
            default               => 0.0,
        };
    }

    /**
     * Get current step number berdasarkan status
     */
    public function getCurrentStep(): int
    {
        $flow = self::getStatusFlow($this->service_type);
        $currentStatus = $this->status ?? 'booking';
        
        // Map old status to new
        $statusMap = [
            'pending' => 'booking',
            'in_progress' => 'document_collection',
            'on_board' => 'on_board',
            'cancel' => 'cancelled',
        ];
        
        $mappedStatus = $statusMap[$currentStatus] ?? $currentStatus;
        
        return isset($flow[$mappedStatus]) ? (int) $flow[$mappedStatus]['order'] : 1;
    }

    /**
     * Check apakah status bisa diupdate ke status baru (tidak boleh mundur)
     */
    public function canUpdateStatusTo(string $newStatus): bool
    {
        $flow = self::getStatusFlow($this->service_type);
        $currentOrder = $this->getCurrentStep();
        $newOrder = isset($flow[$newStatus]) ? $flow[$newStatus]['order'] : 0;
        
        return $newOrder > $currentOrder;
    }
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            self::STATUS_PENDING => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">⏳ Pending</span>',
            self::STATUS_IN_PROGRESS => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">🔄 In Progress</span>',
            self::STATUS_IN_TRANSIT => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">🚚 In Transit</span>',
            self::STATUS_COMPLETED => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✅ Completed</span>',
            self::STATUS_CANCELLED => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">❌ Cancelled</span>',
        ];

        return $badges[$this->status] ?? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">' . $this->status . '</span>';
    }

    /**
     * Get status emoji
     */
    public function getStatusEmojiAttribute(): string
    {
        $emojis = [
            self::STATUS_PENDING => '⏳',
            self::STATUS_IN_PROGRESS => '🔄',
            self::STATUS_IN_TRANSIT => '🚚',
            self::STATUS_COMPLETED => '✅',
            self::STATUS_CANCELLED => '❌',
        ];

        return $emojis[$this->status] ?? '📦';
    }

    /**
     * Check if shipment is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED || $this->status === 'cancel';
    }

    /**
     * Check if shipment can be edited
     */
    public function canBeEdited(): bool
    {
        return !$this->isCancelled();
    }

    /**
     * Cancel this shipment
     */
    public function cancel(int $userId, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $userId,
            'cancellation_reason' => $reason,
        ]);
    }

    // =========================================
    // RELATIONSHIPS
    // =========================================

    /**
     * Relationship to Customer
     */
    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship to Quotation
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Relationship to User who cancelled this shipment
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Relationship to Field Photos
     */
    public function fieldPhotos(): HasMany
    {
        return $this->hasMany(FieldPhoto::class);
    }

    /**
     * Foto lapangan TERBARU (untuk thumbnail dashboard). Tanpa ini,
     * fieldPhotos->first() mengambil foto tertua → thumbnail tak pernah
     * berubah walau ada upload baru.
     */
    public function latestFieldPhoto(): HasOne
    {
        return $this->hasOne(FieldPhoto::class)->latestOfMany();
    }

    /**
     * Relationship to Documents
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'shipment_id');
    }

    public function etaRevisions(): HasMany
    {
        return $this->hasMany(ShipmentEtaRevision::class)->latest();
    }

    public function complianceAudits(): HasMany
    {
        return $this->hasMany(ShipmentComplianceAudit::class)->latest();
    }

    public function latestComplianceAudit(): HasOne
    {
        return $this->hasOne(ShipmentComplianceAudit::class)->latestOfMany();
    }

    /**
     * Item checklist kelengkapan dokumen (F1/F2).
     */
    public function documentRequirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class, 'shipment_id');
    }

    /**
     * Relationship to Shipment Statuses (tracking history)
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(ShipmentStatus::class)->orderBy('created_at', 'desc');
    }

    /**
     * Percakapan pesan customer <-> admin untuk shipment ini.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ShipmentMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get latest status
     */
    public function latestStatus(): HasMany
    {
        return $this->hasMany(ShipmentStatus::class)->latest()->limit(1);
    }

    // =========================================
    // ✅ FIX: TAMBAHKAN RELATIONSHIP JOB COSTS
    // INI YANG MENYEBABKAN ERROR!
    // =========================================

    /**
     * Relationship to Job Costs
     * 
     * One Shipment has Many Job Costs
     * Foreign Key: job_costs.shipment_id -> shipments.id
     */
    public function jobCosts(): HasMany
    {
        return $this->hasMany(JobCost::class);
    }

    /**
     * Get job costs with vendor information (eager loaded)
     */
    public function jobCostsWithVendor(): HasMany
    {
        return $this->hasMany(JobCost::class)->with('vendor');
    }

    /**
     * Get unpaid job costs only
     */
    public function unpaidJobCosts(): HasMany
    {
        return $this->hasMany(JobCost::class)->where('status', 'unpaid');
    }

    /**
     * Get paid job costs only
     */
    public function paidJobCosts(): HasMany
    {
        return $this->hasMany(JobCost::class)->where('status', 'paid');
    }

    // =========================================
    // ACCESSORS FOR JOB COSTS CALCULATIONS
    // =========================================

    /**
     * Get total amount of all job costs
     * Usage: $shipment->total_job_costs
     */
    public function getTotalJobCostsAttribute()
    {
        return $this->jobCosts()->sum('amount');
    }

    /**
     * Get total unpaid job costs
     * Usage: $shipment->total_unpaid_job_costs
     */
    public function getTotalUnpaidJobCostsAttribute()
    {
        return $this->unpaidJobCosts()->sum('amount');
    }

    /**
     * Get total paid job costs
     * Usage: $shipment->total_paid_job_costs
     */
    public function getTotalPaidJobCostsAttribute()
    {
        return $this->paidJobCosts()->sum('amount');
    }

    /**
     * Get count of job costs
     * Usage: $shipment->job_costs_count
     */
    public function getJobCostsCountAttribute()
    {
        return $this->jobCosts()->count();
    }

    // =========================================
    // INVOICES RELATIONSHIP
    // =========================================

    /**
     * Relationship to Invoices
     * 
     * One Shipment can have Many Invoices
     */
    public function invoices(): HasMany
    {
        // Cek apakah tabel invoices ada
        // Jika ada, gunakan relationship normal
        // Jika belum, return empty collection
        
        try {
            return $this->hasMany(Invoice::class);
        } catch (\Exception $e) {
            // Fallback: return empty relationship jika tabel belum ada
            return $this->hasMany(self::class, 'id', 'non_existent_column')->whereRaw('1 = 0');
        }
    }

    /**
     * Get total revenue from invoices
     * Usage: $shipment->total_revenue
     */
    public function getTotalRevenueAttribute()
    {
        try {
            return $this->invoices()->sum('grand_total');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get profit (revenue - costs)
     * Usage: $shipment->profit
     */
    public function getProfitAttribute()
    {
        return $this->total_revenue - $this->total_job_costs;
    }

    /**
     * Get profit margin percentage
     * Usage: $shipment->profit_margin
     */
    public function getProfitMarginAttribute()
    {
        if ($this->total_revenue == 0) {
            return 0;
        }
        
        return (($this->total_revenue - $this->total_job_costs) / $this->total_revenue) * 100;
    }

    // =========================================
    // SCOPES
    // =========================================

    /**
     * Scope: Only active (non-cancelled) shipments
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, 'cancel']);
    }

    /**
     * Scope: Only cancelled shipments
     */
    public function scopeCancelled($query)
    {
        return $query->whereIn('status', [self::STATUS_CANCELLED, 'cancel']);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by shipment type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('shipment_type', $type);
    }

    /**
     * Scope: Filter by service type
     */
    public function scopeByService($query, string $service)
    {
        return $query->where('service_type', $service);
    }

    /**
     * Scope: Filter by customer
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Search by AWB or BL number
     */
    public function scopeSearchByNumber($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('awb_number', 'like', "%{$search}%")
              ->orWhere('bl_number', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Has job costs
     */
    public function scopeHasJobCosts($query)
    {
        return $query->has('jobCosts');
    }

    /**
     * Scope: No job costs yet
     */
    public function scopeWithoutJobCosts($query)
    {
        return $query->doesntHave('jobCosts');
    }

    /**
     * Scope: Low profit margin (< 10%)
     */
    public function scopeLowMargin($query, $threshold = 10)
    {
        return $query->whereHas('invoices')
            ->whereHas('jobCosts')
            ->get()
            ->filter(function($shipment) use ($threshold) {
                return $shipment->profit_margin < $threshold && $shipment->total_revenue > 0;
            });
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    /**
     * Get shipment reference number (AWB or BL)
     */
    public function getReferenceNumberAttribute(): string
    {
        return $this->awb_number ?: ($this->bl_number ?: 'N/A');
    }

    /**
     * Get full route (origin -> destination)
     */
    public function getRouteAttribute(): string
    {
        return "{$this->origin} → {$this->destination}";
    }

    /**
     * Get shipment type icon
     */
    public function getTypeIconAttribute(): string
    {
        $icons = [
            'air' => '✈️',
            'sea' => '🚢',
            'land' => '🚚',
        ];

        return $icons[$this->shipment_type] ?? '📦';
    }

    /**
     * Check if shipment has documents
     */
    public function hasDocuments(): bool
    {
        return $this->documents()->count() > 0;
    }

    /**
     * Check if shipment has job costs
     */
    public function hasJobCosts(): bool
    {
        return $this->jobCosts()->count() > 0;
    }

    /**
     * Check if all job costs are paid
     */
    public function allJobCostsPaid(): bool
    {
        $totalCosts = $this->jobCosts()->count();
        
        if ($totalCosts === 0) {
            return true; // No costs = considered paid
        }
        
        $paidCosts = $this->paidJobCosts()->count();
        
        return $totalCosts === $paidCosts;
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): int
    {
        $steps = [
            'pending' => 0,
            'in_progress' => 33,
            'in_transit' => 66,
            'completed' => 100,
            'cancelled' => 0,
        ];

        return $steps[$this->status] ?? 0;
    }

    /**
     * Get days since created
     */
    public function getDaysSinceCreatedAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get estimated transit time (in days)
     */
    public function getEstimatedTransitDaysAttribute(): ?int
    {
        if (!$this->estimated_departure || !$this->estimated_arrival) {
            return null;
        }

        return $this->estimated_departure->diffInDays($this->estimated_arrival);
    }

    /**
     * Check if shipment is delayed
     */
    public function isDelayed(): bool
    {
        if (!$this->estimated_arrival || $this->status === self::STATUS_COMPLETED) {
            return false;
        }

        return now()->greaterThan($this->estimated_arrival);
    }

    // =========================================
    // LINKED SHIPMENT & CUSTOMS REGIME (DOSSIER)
    // =========================================

    public function parentShipment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_shipment_id');
    }

    public function childShipments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_shipment_id')->orderBy('created_at', 'desc');
    }

    public static function getCustomsRegimes(): array
    {
        return [
            self::REGIME_REGULAR             => 'Reguler (Impor / Ekspor Biasa)',
            self::REGIME_TEMPORARY_IMPORT    => 'Impor Sementara',
            self::REGIME_TEMPORARY_EXPORT    => 'Ekspor Sementara',
            self::REGIME_RE_EXPORT_REJECTION => 'Re-Ekspor (Penolakan / Lartas)',
            self::REGIME_RE_EXPORT_TEMP      => 'Re-Ekspor (Penyelesaian Impor Sementara)',
            self::REGIME_RE_IMPORT_RETURN    => 'Re-Impor (Barang Retur)',
            self::REGIME_RE_IMPORT_TEMP      => 'Re-Impor (Penyelesaian Ekspor Sementara)',
        ];
    }

    public static function getFollowUpRegimesForService(?string $parentServiceType): array
    {
        $service = strtolower((string) $parentServiceType);

        if ($service === 'export') {
            return [
                self::REGIME_RE_IMPORT_RETURN => [
                    'label' => 'Re-Impor (Barang Retur / Ditolak Buyer)',
                    'target_service' => 'import',
                    'hint' => 'Barang ekspor kembali ke Indonesia (maks. 2 tahun dari PEB untuk pembebasan BM/PDRI).',
                ],
                self::REGIME_RE_IMPORT_TEMP => [
                    'label' => 'Re-Impor (Penyelesaian Ekspor Sementara)',
                    'target_service' => 'import',
                    'hint' => 'Barang pameran/perbaikan dari ekspor sementara masuk kembali ke Indonesia.',
                ],
            ];
        }

        return [
            self::REGIME_RE_EXPORT_REJECTION => [
                'label' => 'Re-Ekspor (Penolakan Bea Cukai / Lartas)',
                'target_service' => 'export',
                'hint' => 'Barang impor dilarang/dibatasi atau salah kirim, diputuskan re-ekspor via BC 3.0.',
            ],
            self::REGIME_RE_EXPORT_TEMP => [
                'label' => 'Re-Ekspor (Penyelesaian Impor Sementara)',
                'target_service' => 'export',
                'hint' => 'Pengeluaran kembali barang impor sementara sebelum batas waktu izin BC.',
            ],
        ];
    }

    public function getCustomsRegimeLabel(): string
    {
        $regime = $this->customs_regime ?: self::REGIME_REGULAR;
        return self::getCustomsRegimes()[$regime] ?? 'Reguler';
    }

    public function getCustomsRegimeShortLabel(): string
    {
        return match ($this->customs_regime ?: self::REGIME_REGULAR) {
            self::REGIME_TEMPORARY_IMPORT    => 'Impor Sementara',
            self::REGIME_TEMPORARY_EXPORT    => 'Ekspor Sementara',
            self::REGIME_RE_EXPORT_REJECTION => 'Re-Ekspor (Lartas/Tolak)',
            self::REGIME_RE_EXPORT_TEMP      => 'Re-Ekspor (Impor Sementara)',
            self::REGIME_RE_IMPORT_RETURN    => 'Re-Impor (Retur)',
            self::REGIME_RE_IMPORT_TEMP      => 'Re-Impor (Ekspor Sementara)',
            default                          => 'Reguler',
        };
    }

    public function getCustomsRegimeBadgeClass(): string
    {
        return match ($this->customs_regime ?: self::REGIME_REGULAR) {
            self::REGIME_TEMPORARY_IMPORT,
            self::REGIME_TEMPORARY_EXPORT    => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::REGIME_RE_EXPORT_REJECTION => 'bg-rose-100 text-rose-800 border-rose-200',
            self::REGIME_RE_EXPORT_TEMP      => 'bg-amber-100 text-amber-800 border-amber-200',
            self::REGIME_RE_IMPORT_RETURN,
            self::REGIME_RE_IMPORT_TEMP      => 'bg-teal-100 text-teal-800 border-teal-200',
            default                          => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function hasSpecialCustomsRegime(): bool
    {
        return !empty($this->customs_regime) && $this->customs_regime !== self::REGIME_REGULAR;
    }

    public function isCustomsDeadlineOverdue(): bool
    {
        if (!$this->customs_deadline_at) {
            return false;
        }
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            return false;
        }

        return $this->customs_deadline_at->isPast() && !$this->customs_deadline_at->isToday();
    }

    public function isCustomsDeadlineApproaching(int $days = 30): bool
    {
        if (!$this->customs_deadline_at || $this->isCustomsDeadlineOverdue()) {
            return false;
        }
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            return false;
        }

        return now()->startOfDay()->diffInDays($this->customs_deadline_at->startOfDay(), false) <= $days;
    }

    /**
     * Ambil seluruh shipment terkait dalam satu dossier (induk + anak + saudara jika ada), tidak termasuk diri sendiri.
     */
    public function getLinkedShipments()
    {
        $rootId = $this->parent_shipment_id ?: $this->id;

        return self::with(['documents'])
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)
                  ->orWhere('parent_shipment_id', $rootId);
            })
            ->where('id', '!=', $this->id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Daftar ID shipment yang berada dalam satu dossier (termasuk diri sendiri).
     */
    public function getDossierShipmentIds(): array
    {
        $rootId = $this->parent_shipment_id ?: $this->id;

        return self::where(function ($q) use ($rootId) {
                $q->where('id', $rootId)
                  ->orWhere('parent_shipment_id', $rootId);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
