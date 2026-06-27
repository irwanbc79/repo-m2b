<?php

namespace App\Livewire\Admin;

use App\Models\Shipment;
use App\Models\ShipmentMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerMessages extends Component
{
    public ?int $selectedShipmentId = null;
    public string $reply = '';
    public bool $onlyUnread = false;

    public function selectThread(int $shipmentId): void
    {
        $this->selectedShipmentId = $shipmentId;
        $this->reply = '';

        // Tandai pesan customer di thread ini sebagai sudah dibaca admin.
        ShipmentMessage::where('shipment_id', $shipmentId)
            ->unreadForAdmin()
            ->update(['read_at' => now()]);
    }

    public function sendReply(): void
    {
        $this->validate(
            ['reply' => 'required|string|max:2000'],
            ['reply.required' => 'Balasan tidak boleh kosong.']
        );

        $shipment = Shipment::findOrFail($this->selectedShipmentId);

        $shipment->messages()->create([
            'customer_id' => $shipment->customer_id,
            'sender_type' => 'admin',
            'sender_id'   => Auth::id(),
            'body'        => trim($this->reply),
        ]);

        $this->reply = '';
        session()->flash('reply_sent', 'Balasan terkirim ke customer.');
    }

    public function render()
    {
        $threads = Shipment::query()
            ->whereHas('messages')
            ->with('customer')
            ->withCount(['messages as unread_count' => fn ($q) => $q->unreadForAdmin()])
            ->withMax('messages', 'created_at')
            ->when($this->onlyUnread, fn ($q) => $q->having('unread_count', '>', 0))
            ->orderByDesc('unread_count')
            ->orderByDesc('messages_max_created_at')
            ->get();

        $selected = $this->selectedShipmentId
            ? Shipment::with(['customer', 'messages.sender'])->find($this->selectedShipmentId)
            : null;

        return view('livewire.admin.customer-messages', [
            'threads'  => $threads,
            'selected' => $selected,
        ])->layout('layouts.admin');
    }
}
