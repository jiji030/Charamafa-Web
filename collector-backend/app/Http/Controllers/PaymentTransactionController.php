<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentTransactionController extends Controller
{
    public function index()
    {
        try {
            $transactions = DB::table('payment_transactions')
                ->leftJoin('users', 'payment_transactions.processed_by', '=', 'users.admin_id')
                ->select(
                    'payment_transactions.*',
                    DB::raw("users.fname || ' ' || COALESCE(users.mname || ' ', '') || users.lname || COALESCE(' ' || users.suffix, '') as processed_by_name")
                )
                ->orderBy('payment_transactions.payment_date', 'desc')
                ->get();

            return response()->json($transactions);
        } catch (\Exception $e) {
            \Log::error('Payment transactions index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:completed,pending,cancelled'
        ]);

        $validated['status'] = $validated['status'] ?? 'completed';
        $validated['processed_by'] = auth()->id();
        $validated['created_at'] = now();

        try {
            $transactionId = DB::table('payment_transactions')->insertGetId($validated);

            return response()->json([
                'message' => 'Transaction created successfully',
                'transaction_id' => $transactionId
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'status' => 'required|string|in:completed,pending,cancelled',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::table('payment_transactions')
                ->where('transaction_id', $id)
                ->update($validated);

            return response()->json([
                'message' => 'Transaction updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('payment_transactions')
                ->where('transaction_id', $id)
                ->delete();

            return response()->json([
                'message' => 'Transaction deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}