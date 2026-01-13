<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\SimpleInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class SimpleInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SimpleInvoice::with('creator');
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }
        
        // Filter by year
        if ($request->filled('year')) {
            $query->whereYear('invoice_date', $request->year);
        }
        
        // Filter by month
        if ($request->filled('month')) {
            $query->whereMonth('invoice_date', $request->month);
        }
        
        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $invoices = $query->paginate(20)->appends($request->except('page'));
        
        // Statistics
        $stats = [
            'total' => SimpleInvoice::count(),
            'unpaid' => SimpleInvoice::where('status', 'unpaid')->count(),
            'paid' => SimpleInvoice::where('status', 'paid')->count(),
            'total_amount' => SimpleInvoice::sum('total'),
            'unpaid_amount' => SimpleInvoice::where('status', 'unpaid')->sum('total'),
            'paid_amount' => SimpleInvoice::where('status', 'paid')->sum('total'),
        ];
        
        // Available years
        $years = SimpleInvoice::selectRaw('YEAR(invoice_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
        
        return view('finance.simple-invoice.index', compact('invoices', 'stats', 'years'));
    }

    public function create()
    {
        return view('finance.simple-invoice.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'nullable|string',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Create invoice
        $invoice = SimpleInvoice::create([
            'customer_name' => $validated['customer_name'],
            'customer_address' => $validated['customer_address'],
            'invoice_date' => $validated['invoice_date'],
            'notes' => $validated['notes'],
            'currency' => 'IDR',
            'status' => 'unpaid',
            'created_by' => auth()->id(),
        ]);

        // Create items
        $total = 0;
        foreach ($validated['items'] as $index => $item) {
            $amount = $item['quantity'] * $item['unit_price'];
            $total += $amount;
            
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $amount,
                'sort_order' => $index + 1,
            ]);
        }

        // Update total
        $invoice->update(['total' => $total]);

        return redirect()->route('finance.simple-invoice.index')
            ->with('success', 'Invoice berhasil dibuat: ' . $invoice->invoice_number);
    }

    public function pdf($id)
    {
        $invoice = SimpleInvoice::with('items')->findOrFail($id);
        return view('finance.simple-invoice.pdf', compact('invoice'));
    }
    
    public function download($id)
    {
        $invoice = SimpleInvoice::with('items')->findOrFail($id);
        
        $pdf = Pdf::loadView('finance.simple-invoice.pdf-print', compact('invoice'));
        $pdf->setPaper('a4', 'portrait');
        
        $filename = 'Invoice_' . $invoice->invoice_number . '.pdf';
        $filename = str_replace('/', '-', $filename);
        
        return $pdf->download($filename);
    }
    
    public function edit($id)
    {
        $invoice = SimpleInvoice::with('items')->findOrFail($id);
        return view('finance.simple-invoice.edit', compact('invoice'));
    }
    
    public function detail($id)
{
    $invoice = SimpleInvoice::with('items')->findOrFail($id);
    return view('finance.simple-invoice.detail', compact('invoice'));
}
    
    public function update(Request $request, $id)
    {
        $invoice = SimpleInvoice::findOrFail($id);
        
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'nullable|string',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Update invoice
        $invoice->update([
            'customer_name' => $validated['customer_name'],
            'customer_address' => $validated['customer_address'],
            'invoice_date' => $validated['invoice_date'],
            'notes' => $validated['notes'],
        ]);

        // Delete old items and create new ones
        $invoice->items()->delete();
        
        $total = 0;
        foreach ($validated['items'] as $index => $item) {
            $amount = $item['quantity'] * $item['unit_price'];
            $total += $amount;
            
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $amount,
                'sort_order' => $index + 1,
            ]);
        }

        // Update total
        $invoice->update(['total' => $total]);

        return redirect()->route('finance.simple-invoice.index')
            ->with('success', 'Invoice berhasil diupdate: ' . $invoice->invoice_number);
    }
    
    public function destroy($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'director'])) {
            return redirect()->back()->with('error', 'Unauthorized');
        }
        
        $invoice = SimpleInvoice::findOrFail($id);
        $invoiceNumber = $invoice->invoice_number;
        $invoice->delete();
        
        return redirect()->route('finance.simple-invoice.index')
            ->with('success', 'Invoice deleted: ' . $invoiceNumber);
    }
    
    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:paid,unpaid',
            'paid_date' => 'required_if:status,paid|nullable|date',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'payment_notes' => 'nullable|string'
        ]);
        
        $invoice = SimpleInvoice::findOrFail($id);
        $invoice->status = $request->status;
        $invoice->paid_date = $request->status === 'paid' ? $request->paid_date : null;
        $invoice->payment_notes = $request->payment_notes;
        
        if ($request->hasFile('payment_proof')) {
            if ($invoice->payment_proof && Storage::disk('public')->exists($invoice->payment_proof)) {
                Storage::disk('public')->delete($invoice->payment_proof);
            }
            $file = $request->file('payment_proof');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('payment_proofs', $filename, 'public');
            $invoice->payment_proof = $path;
        }
        
        $invoice->save();
        
        return redirect()->back()->with('success', 'Payment status updated');
    }
}
