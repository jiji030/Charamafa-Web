<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreasurerDashboardController extends Controller
{
    public function getStats()
    {
        $totalCollections = DB::table('collections')->sum('total_collection');
        $totalExpenses = DB::table('expenses')->sum('amount');
        $activeMembers = DB::table('members')->where('connection_status', 1)->count();
        $pendingPayments = DB::table('members')->where('prev_balance', '>', 0)->count();

        return response()->json([
            'total_collections' => $totalCollections,
            'total_expenses' => $totalExpenses,
            'active_members' => $activeMembers,
            'pending_payments' => $pendingPayments
        ]);
    }
}