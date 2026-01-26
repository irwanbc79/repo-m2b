<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\Shipment;
use App\Mail\ShipmentStatusUpdate;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Create a notification for a user.
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Send shipment status update notification.
     */
    public function notifyShipmentStatusUpdate(Shipment $shipment): void
    {
        $customer = $shipment->customer;
        $user = $customer->user;

        // Create in-app notification
        $this->create(
            $user,
            'shipment_update',
            'Shipment Status Updated',
            "Your shipment {$shipment->awb_number} status has been updated to {$shipment->status}",
            [
                'shipment_id' => $shipment->id,
                'awb_number' => $shipment->awb_number,
                'status' => $shipment->status,
            ]
        );

        // Send email notification
        try {
            Mail::to($user->email)->send(new ShipmentStatusUpdate($shipment));

            // Log ke sent_emails
            \App\Models\SentEmail::create([
                'mailbox' => 'no_reply',
                'to_email' => $user->email,
                'subject' => 'Status Update: ' . $shipment->awb_number,
                'body' => 'Notifikasi status shipment ' . $shipment->status,
                'user_id' => auth()->id() ?? 1,
                'user_name' => auth()->user()->name ?? 'System',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send shipment status email: ' . $e->getMessage());
        }
    }

    /**
     * Send document uploaded notification.
     */
    public function notifyDocumentUploaded(Shipment $shipment, string $filename): void
    {
        $customer = $shipment->customer;
        $user = $customer->user;

        // Create in-app notification
        $this->create(
            $user,
            'document_uploaded',
            'New Document Uploaded',
            "A new document '{$filename}' has been uploaded for shipment {$shipment->awb_number}",
            [
                'shipment_id' => $shipment->id,
                'awb_number' => $shipment->awb_number,
                'filename' => $filename,
            ]
        );
    }

    /**
     * Send system notification to all users or specific role.
     */
    public function notifySystem(
        string $title,
        string $message,
        ?string $role = null
    ): int {
        $query = User::query();

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            $this->create(
                $user,
                'system',
                $title,
                $message
            );
            $count++;
        }

        return $count;
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Delete old read notifications.
     */
    public function cleanupOldNotifications(int $days = 30): int
    {
        return Notification::whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays($days))
            ->delete();
    }
}
