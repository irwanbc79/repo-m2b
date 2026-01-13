<?php

namespace App\Livewire\Finance;

use Livewire\Component;
use App\Models\SimpleInvoice;
use App\Models\SimpleInvoiceItem;

class EditSimpleInvoice extends Component
{
    public $invoiceId;
    public $customer_name = '';
    public $customer_address = '';
    public $invoice_date;
    public $notes = '';
    public $items = [];
    
    protected $rules = [
        'customer_name' => 'required|max:255',
        'customer_address' => 'nullable',
        'invoice_date' => 'required|date',
        'items.*.description' => 'required|max:500',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_price' => 'required|numeric|min:0',
    ];

    public function mount($invoiceId)
    {
        $invoice = SimpleInvoice::with('items')->findOrFail($invoiceId);
        
        $this->invoiceId = $invoice->id;
        $this->customer_name = $invoice->customer_name;
        $this->customer_address = $invoice->customer_address;
        $this->invoice_date = $invoice->invoice_date->format('Y-m-d');
        $this->notes = $invoice->notes;
        
        foreach ($invoice->items as $item) {
            $this->items[] = [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'amount' => $item->amount,
            ];
        }
    }

    public function addItem()
    {
        $this->items[] = [
            'id' => null,
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'amount' => 0,
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        
        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function updatedItems($value, $key)
    {
        $parts = explode('.', $key);
        $index = $parts[0];
        
        if (isset($this->items[$index])) {
            $qty = (float)($this->items[$index]['quantity'] ?? 0);
            $price = (float)($this->items[$index]['unit_price'] ?? 0);
            $this->items[$index]['amount'] = $qty * $price;
        }
    }

    public function getSubtotalProperty()
    {
        return collect($this->items)->sum('amount');
    }

    public function getTerbilangProperty()
    {
        return SimpleInvoice::numberToWords($this->subtotal, 'IDR');
    }

    public function update()
    {
        $this->validate();

        $invoice = SimpleInvoice::findOrFail($this->invoiceId);
        
        $invoice->update([
            'invoice_date' => $this->invoice_date,
            'customer_name' => $this->customer_name,
            'customer_address' => $this->customer_address,
            'subtotal' => $this->subtotal,
            'total' => $this->subtotal,
            'terbilang' => $this->terbilang,
            'notes' => $this->notes,
        ]);

        // Delete old items
        $invoice->items()->delete();
        
        // Create new items
        foreach ($this->items as $index => $item) {
            SimpleInvoiceItem::create([
                'simple_invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['amount'],
                'sort_order' => $index,
            ]);
        }

        session()->flash('success', 'Invoice updated successfully');
        return redirect()->route('finance.simple-invoice.index');
    }

    public function render()
    {
        return view('livewire.finance.edit-simple-invoice')
            ->layout('layouts.admin');
    }
}
