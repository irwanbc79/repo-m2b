<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentStatus;
use App\Models\ActivityLog;
use App\Events\ShipmentStatusUpdated;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    /**
     * Create a new shipment.
     */
    public function createShipment(array $data): Shipment
    {
        return DB::transaction(function () use ($data) {
            $shipment = Shipment::create($data);

            // Create initial status
            ShipmentStatus::create([
                'shipment_id' => $shipment->id,
                'status' => 'Shipment Created',
                'location' => $data['origin'],
                'notes' => 'Shipment has been created in the system',
                'updated_by' => auth()->id(),
            ]);

            // Log activity
            ActivityLog::log(
                'created',
                "Created shipment {$shipment->awb_number}",
                $shipment
            );

            return $shipment->load('customer', 'statusHistory');
        });
    }

    /**
     * Update shipment status.
     */
    public function updateStatus(
        Shipment $shipment,
        string $status,
        string $location,
        ?string $notes = null
    ): ShipmentStatus {
        return DB::transaction(function () use ($shipment, $status, $location, $notes) {
            $oldStatus = $shipment->status;

            // Update shipment status
            $shipment->update(['status' => $status]);

            // Create status history record
            $statusRecord = ShipmentStatus::create([
                'shipment_id' => $shipment->id,
                'status' => $status,
                'location' => $location,
                'notes' => $notes,
                'updated_by' => auth()->id(),
            ]);

            // Log activity
            ActivityLog::log(
                'updated',
                "Updated shipment {$shipment->awb_number} status from {$oldStatus} to {$status}",
                $shipment,
                ['status' => $oldStatus],
                ['status' => $status]
            );

            // Trigger event for notifications
            event(new ShipmentStatusUpdated($shipment));

            return $statusRecord;
        });
    }

    /**
     * Get tracking timeline for a shipment.
     */
    public function getTrackingTimeline(Shipment $shipment): array
    {
        return ShipmentStatus::where('shipment_id', $shipment->id)
            ->with('updatedBy')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($status) {
                return [
                    'id' => $status->id,
                    'status' => $status->status,
                    'location' => $status->location,
                    'notes' => $status->notes,
                    'timestamp' => $status->created_at->format('d M Y, H:i'),
                    'relative_time' => $status->created_at->diffForHumans(),
                    'updated_by' => $status->updatedBy->full_name,
                ];
            })
            ->toArray();
    }

    /**
     * Update shipment details.
     */
    public function updateShipment(Shipment $shipment, array $data): Shipment
    {
        return DB::transaction(function () use ($shipment, $data) {
            $oldValues = $shipment->only(array_keys($data));
            
            $shipment->update($data);

            // Log activity
            ActivityLog::log(
                'updated',
                "Updated shipment {$shipment->awb_number} details",
                $shipment,
                $oldValues,
                $data
            );

            return $shipment->fresh();
        });
    }

    /**
     * Delete shipment.
     */
    public function deleteShipment(Shipment $shipment): bool
    {
        return DB::transaction(function () use ($shipment) {
            $awbNumber = $shipment->awb_number;

            // Delete all documents from storage
            foreach ($shipment->documents as $document) {
                $document->deleteFile();
            }

            // Delete shipment (cascade will delete related records)
            $shipment->delete();

            // Log activity
            ActivityLog::log(
                'deleted',
                "Deleted shipment {$awbNumber}"
            );

            return true;
        });
    }

    /**
     * Get shipment statistics for a customer.
     */
    public function getCustomerStatistics(int $customerId): array
    {
        $shipments = Shipment::where('customer_id', $customerId);

        return [
            'total' => $shipments->count(),
            'pending' => $shipments->where('status', 'pending')->count(),
            'in_progress' => $shipments->where('status', 'in_progress')->count(),
            'completed' => $shipments->where('status', 'completed')->count(),
            'cancelled' => $shipments->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Get overall shipment statistics.
     */
    public function getOverallStatistics(): array
    {
        return [
            'total' => Shipment::count(),
            'pending' => Shipment::where('status', 'pending')->count(),
            'in_progress' => Shipment::where('status', 'in_progress')->count(),
            'completed' => Shipment::where('status', 'completed')->count(),
            'cancelled' => Shipment::where('status', 'cancelled')->count(),
            'this_month' => Shipment::whereMonth('created_at', now()->month)->count(),
            'delayed' => Shipment::where('status', 'in_progress')
                ->where('estimated_arrival', '<', now())
                ->count(),
        ];
    }
}
