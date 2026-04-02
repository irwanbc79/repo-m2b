<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpenseClaim;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private FonnteService $fonnte) {}

    public function index(Request $request): JsonResponse
    {
        $expenses = ExpenseClaim::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Daftar klaim biaya.',
            'data'    => $expenses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category'    => 'required|in:meal,travel,hotel,supplies,other',
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'receipt'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('expenses/receipts', 'public');
        }

        $expense = ExpenseClaim::create([
            'user_id'      => $request->user()->id,
            'category'     => $request->category,
            'amount'       => $request->amount,
            'description'  => $request->description,
            'receipt_path' => $receiptPath,
            'status'       => 'pending',
        ]);

        $this->notifyManagerNewExpense($expense, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Klaim berhasil dikirim.',
            'data'    => $expense,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $expense = ExpenseClaim::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail klaim.',
            'data'    => $expense->load('approver:id,name'),
        ]);
    }

    // ─── WA Notifications ───────────────────────────────────────────────────────

    private function notifyManagerNewExpense(ExpenseClaim $expense, User $employee): void
    {
        $managers = User::where(function ($q) {
            $q->whereJsonContains('roles', 'manager')
              ->orWhereJsonContains('roles', 'admin')
              ->orWhere('role', 'manager')
              ->orWhere('role', 'admin');
        })->whereNotNull('phone')->get();

        $amount   = number_format($expense->amount, 0, ',', '.');
        $category = ucfirst($expense->category);
        $message  = "Klaim biaya dari *{$employee->name}*: Rp {$amount} - {$category}. Mohon review di portal.";

        foreach ($managers as $manager) {
            $this->fonnte->send($manager->phone, $message);
        }
    }
}
