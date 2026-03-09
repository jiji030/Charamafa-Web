<?php
// app/Http/Controllers/ExpenseController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = DB::table('expenses')
            ->orderBy('date', 'desc')
            ->get();
            
        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'expense_type' => 'required|string',
            'what_was_bought_paid_for' => 'required|string',
            'where_it_was_used' => 'required|string',
            'amount' => 'required|numeric|min:0'
        ]);

        $expenseId = DB::table('expenses')->insertGetId([
            'date' => $request->date,
            'expense_type' => $request->expense_type,
            'what_was_bought_paid_for' => $request->what_was_bought_paid_for,
            'where_it_was_used' => $request->where_it_was_used,
            'amount' => $request->amount,
            'created_by' => auth()->id(),
            'created_at' => now()
        ]);

        return response()->json([
            'message' => 'Expense created successfully',
            'expense_id' => $expenseId
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'expense_type' => 'required|string|max:255',
            'what_was_bought_paid_for' => 'required|string|max:500',
            'where_it_was_used' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0'
        ]);

        try {
            DB::table('expenses')
                ->where('expense_id', $id)
                ->update($validated);

            return response()->json([
                'message' => 'Expense updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update expense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::table('expenses')->where('expense_id', $id)->delete();
        
        return response()->json(['message' => 'Expense deleted successfully']);
    }
}
