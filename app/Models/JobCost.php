<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class JobCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'vendor_id',
        'description',
        'amount',
        'coa_id',
        'status',
        'date_paid',
        'proof_file',
        'created_by',
        'journal_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date_paid' => 'date',
    ];

    // =========================================
    // RELATIONSHIPS
    // =========================================

    /**
     * Relationship ke Shipment
     * Inverse of Shipment->jobCosts()
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Relationship ke Vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * ✅ FIX: Relationship ke Account (COA)
     * Foreign key: coa_id
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'coa_id');
    }

    /**
     * Alias untuk account relationship
     * Untuk backward compatibility jika ada code yang pakai coaAccount
     */
    public function coaAccount(): BelongsTo
    {
        return $this->account();
    }

    /**
     * Relationship ke User (created_by)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship ke Journal (if journal created)
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    // =========================================
    // SCOPES
    // =========================================

    /**
     * Scope: Only unpaid costs
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    /**
     * Scope: Only paid costs
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: By shipment
     */
    public function scopeByShipment($query, $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }

    /**
     * Scope: By vendor
     */
    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope: Has proof file
     */
    public function scopeHasProof($query)
    {
        return $query->whereNotNull('proof_file');
    }

    /**
     * Scope: Date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // =========================================
    // ACCESSORS & MUTATORS
    // =========================================

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'paid' => '<span class="px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800">PAID</span>',
            'unpaid' => '<span class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-100 text-yellow-800">UNPAID</span>',
        ];

        return $badges[$this->status] ?? $this->status;
    }

    /**
     * Get proof file URL
     */
    public function getProofUrlAttribute(): ?string
    {
        if (!$this->proof_file) {
            return null;
        }

        return Storage::url($this->proof_file);
    }

    /**
     * Check if proof file exists
     */
    public function hasProof(): bool
    {
        return !empty($this->proof_file) && Storage::disk('public')->exists($this->proof_file);
    }

    /**
     * Get proof file extension
     */
    public function getProofExtensionAttribute(): ?string
    {
        if (!$this->proof_file) {
            return null;
        }

        return strtolower(pathinfo($this->proof_file, PATHINFO_EXTENSION));
    }

    /**
     * Check if proof is image
     */
    public function isProofImage(): bool
    {
        if (!$this->proof_extension) {
            return false;
        }

        return in_array($this->proof_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Check if proof is PDF
     */
    public function isProofPdf(): bool
    {
        return $this->proof_extension === 'pdf';
    }

    /**
     * Check if paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if unpaid
     */
    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    /**
     * Check if has journal entry
     */
    public function hasJournal(): bool
    {
        return !empty($this->journal_id);
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    /**
     * Mark as paid
     */
    public function markAsPaid(?string $date = null): void
    {
        $this->update([
            'status' => 'paid',
            'date_paid' => $date ?? now(),
        ]);
    }

    /**
     * Mark as unpaid
     */
    public function markAsUnpaid(): void
    {
        $this->update([
            'status' => 'unpaid',
            'date_paid' => null,
        ]);
    }

    /**
     * Delete proof file
     */
    public function deleteProof(): bool
    {
        if ($this->proof_file && Storage::disk('public')->exists($this->proof_file)) {
            return Storage::disk('public')->delete($this->proof_file);
        }

        return false;
    }

    /**
     * Get days since created
     */
    public function getDaysSinceCreatedAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get days since paid
     */
    public function getDaysSincePaidAttribute(): ?int
    {
        if (!$this->date_paid) {
            return null;
        }

        return $this->date_paid->diffInDays(now());
    }

    /**
     * Check if overdue (unpaid for more than X days)
     */
    public function isOverdue(int $days = 30): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        return $this->days_since_created > $days;
    }

    // =========================================
    // EVENTS
    // =========================================

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Delete proof file when JobCost is deleted
        static::deleting(function ($jobCost) {
            $jobCost->deleteProof();
        });
    }
}