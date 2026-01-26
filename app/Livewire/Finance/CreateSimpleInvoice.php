<?php

namespace App\Livewire\Finance;

use Livewire\Component;
use App\Models\SimpleInvoice;
use App\Models\SimpleInvoiceItem;

class CreateSimpleInvoice extends Component
{
    public $customer_name = '';
    public $customer_address = '';
    public $customer_id = null;
    public $invoice_date;
    public $due_date;
    public $currency = 'IDR'; // Fixed to IDR only
    public $notes = '';
    
    public $items = [];
    
    protected $rules = [
        'customer_name' => 'required|max:255',
        'customer_address' => 'nullable',
        'invoice_date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:invoice_date',
        'items.*.description' => 'required|max:500',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_price' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(7)->format('Y-m-d');
        $this->addItem();
    }

    public function addItem()
    {
        $this->items[] = [
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

    public function save()
    {
        $this->validate();

        if (empty($this->items)) {
            session()->flash('error', 'Minimal 1 item diperlukan');
            return;
        }

        $invoice = SimpleInvoice::create([
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'customer_name' => $this->customer_name,
            'customer_address' => $this->customer_address,
            'customer_id' => $this->customer_id,
            'currency' => 'IDR', // Always IDR
            'subtotal' => $this->subtotal,
            'total' => $this->subtotal,
            'notes' => $this->notes,
            'created_by' => auth()->id(),
        ]);

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

        session()->flash('success', 'Invoice berhasil dibuat: ' . $invoice->invoice_number);
        return redirect()->route('finance.simple-invoice.pdf', $invoice->id);
    }

    public function render()
    {
        return view('livewire.finance.create-simple-invoice')
            ->layout('layouts.admin');
    }
}
