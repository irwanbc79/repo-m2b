<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\SimpleInvoice;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class SimpleInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SimpleInvoice::with(['creator', 'items']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                    ->orWhere('customer_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('description', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Filter by status - whitelist untuk keamanan
        if ($request->filled('status') && in_array($request->status, ['unpaid', 'paid', 'cancelled'])) {
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

        // Sort - whitelist untuk mencegah SQL injection
        $allowedSort = ['created_at', 'invoice_date', 'invoice_number', 'customer_name', 'total', 'status'];
        if (!$request->filled('sort_by')) {
            $query->orderByRaw("CASE WHEN status = 'unpaid' THEN 0 ELSE 1 END")
                  ->orderBy('invoice_date', 'desc')
                  ->orderBy('id', 'desc');
        } else {
            $sortBy = in_array($request->get('sort_by'), $allowedSort) ? $request->get('sort_by') : 'created_at';
            $sortOrder = strtolower($request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortOrder);
        }

        $invoices = $query->paginate(20)->appends($request->except('page'));

        // Statistics - di-cache 5 menit untuk mengurangi query
        $stats = Cache::remember('simple_invoice_stats', 300, function () {
            $aggregates = SimpleInvoice::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid,
                SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid,
                SUM(total) as total_amount,
                SUM(CASE WHEN status = "unpaid" THEN total ELSE 0 END) as unpaid_amount,
                SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) as paid_amount
            ')->first();
            return [
            'total' => (int)($aggregates->total ?? 0),
            'unpaid' => (int)($aggregates->unpaid ?? 0),
            'paid' => (int)($aggregates->paid ?? 0),
            'total_amount' => (float)($aggregates->total_amount ?? 0),
            'unpaid_amount' => (float)($aggregates->unpaid_amount ?? 0),
            'paid_amount' => (float)($aggregates->paid_amount ?? 0),
            ];
        });

        // Available years - cache 1 jam
        $years = Cache::remember('simple_invoice_years', 3600, function () {
            return SimpleInvoice::selectRaw('YEAR(invoice_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
        });

        return view('finance.simple-invoice.index', compact('invoices', 'stats', 'years'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::orderBy('is_system', 'desc')->orderBy('created_at', 'asc')->get();
        return view('finance.simple-invoice.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'nullable|string',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
            'save_bank_account' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Create invoice
        $invoice = SimpleInvoice::create([
            'customer_name' => $validated['customer_name'],
            'customer_address' => $validated['customer_address'] ?? null,
            'invoice_date' => $validated['invoice_date'],
            'notes' => $validated['notes'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_account_holder' => $validated['bank_account_holder'] ?? null,
            'currency' => 'IDR',
            'status' => 'unpaid',
            'created_by' => auth()->id(),
        ]);

        // Save bank account as template if checked
        if ($request->boolean('save_bank_account') && !empty($validated['bank_account_number'])) {
            BankAccount::firstOrCreate(
                ['account_number' => $validated['bank_account_number']],
                [
                    'bank_name' => $validated['bank_name'] ?? '',
                    'account_holder' => $validated['bank_account_holder'] ?? '',
                    'is_system' => false,
                ]
            );
        }

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

        Cache::forget('simple_invoice_stats');
        Cache::forget('simple_invoice_years');

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
        $bankAccounts = BankAccount::orderBy('is_system', 'desc')->orderBy('created_at', 'asc')->get();
        return view('finance.simple-invoice.edit', compact('invoice', 'bankAccounts'));
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
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
            'save_bank_account' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Update invoice
        $invoice->update([
            'customer_name' => $validated['customer_name'],
            'customer_address' => $validated['customer_address'] ?? null,
            'invoice_date' => $validated['invoice_date'],
            'notes' => $validated['notes'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_account_holder' => $validated['bank_account_holder'] ?? null,
        ]);

        // Save bank account as template if checked
        if ($request->boolean('save_bank_account') && !empty($validated['bank_account_number'])) {
            BankAccount::firstOrCreate(
                ['account_number' => $validated['bank_account_number']],
                [
                    'bank_name' => $validated['bank_name'] ?? '',
                    'account_holder' => $validated['bank_account_holder'] ?? '',
                    'is_system' => false,
                ]
            );
        }

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

        Cache::forget('simple_invoice_stats');
        Cache::forget('simple_invoice_years');

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

        Cache::forget('simple_invoice_stats');
        Cache::forget('simple_invoice_years');

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

        Cache::forget('simple_invoice_stats');

        return redirect()->back()->with('success', 'Payment status updated');
    }

    /**
     * Print Simple Invoice - Ukuran 9.5" x 11" (Continuous Form)
     */
    public function print($id)
    {
        $invoice = SimpleInvoice::with(['items', 'customer', 'creator'])->findOrFail($id);
        return view('finance.simple-invoice.print', compact('invoice'));
    }
}