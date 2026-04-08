<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReconnectionController;
use App\Http\Controllers\DisconnectionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\PaymentTransactionController;
use App\Http\Controllers\TreasurerDashboardController;
use App\Http\Controllers\MasterListController;
use App\Http\Controllers\ImportantInformationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PurokController;
use App\Http\Controllers\TsNumberController;
use App\Http\Controllers\WaterConsumptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrBookletController;
use App\Http\Controllers\MonthlyArchiveController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Special endpoint for sync authentication
Route::post('/auth/token-for-sync', [AuthController::class, 'createSyncToken']);

Route::get('/test', function () {
    return response()->json(['status' => 'ok', 'message' => 'Server is reachable']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
      // Collector routes
    Route::get('/members', [MemberController::class, 'index']);
    
    // Get shared meter members for a specific meter number (must come before /members/{id})
    Route::get('/members/shared-meter/{meter_no}', function($meterNo) {
        $members = DB::table('members')
            ->where('meter_no', $meterNo)
            ->whereNotNull('meter_no')
            ->where('meter_no', '!=', '')
            ->select('member_id', 'account_no', 'fname', 'lname', 'mname', 'suffix', 'connection_status', 'is_paid', 'prev_balance')
            ->get()
            ->map(function($member) {
                return [
                    'member_id' => $member->member_id,
                    'account_no' => $member->account_no,
                    'full_name' => trim("{$member->fname} {$member->mname} {$member->lname} {$member->suffix}"),
                    'connection_status' => $member->connection_status,
                    'is_paid' => $member->is_paid,
                    'prev_balance' => $member->prev_balance
                ];
            });
        
        return response()->json([
            'shared_members' => $members
        ]);
    });
    
    Route::get('/members/{id}', [MemberController::class, 'show']);
    Route::post('/members/{id}/disconnect', [MemberController::class, 'disconnect']);
    Route::post('/members/{id}/photo', [MemberController::class, 'uploadPhoto']);
    Route::delete('/members/{id}/photo', [MemberController::class, 'deletePhoto']);    Route::put('/members/{id}/damage-charges', [MemberController::class, 'updateDamageCharges']);
    Route::post('/members/{id}/reset-consumption', [MemberController::class, 'resetWaterConsumption']);
    Route::get('/reconnection/{id}', [ReconnectionController::class, 'show']);
    Route::post('/reconnection/{id}', [ReconnectionController::class, 'processReconnection']);
    Route::post('/reconnection/{id}/pay-balance', [ReconnectionController::class, 'payBalanceOnly']);
    Route::delete('/members/{id}', [MemberController::class, 'destroy']);
    
    // Disconnection routes (with shared meter support)
    Route::post('/disconnection/{id}', [DisconnectionController::class, 'disconnect']);
    Route::get('/disconnection/{id}/history', [DisconnectionController::class, 'getDisconnectionHistory']);
    Route::get('/disconnected-members', [DisconnectionController::class, 'getDisconnectedMembers']);
    
    // Shared meter routes
    Route::get('/shared-meters', function() {
        $sharedMeters = DB::table('members')
            ->select('meter_no', DB::raw('COUNT(*) as member_count'))
            ->whereNotNull('meter_no')
            ->where('meter_no', '!=', '')
            ->groupBy('meter_no')
            ->having('member_count', '>', 1)
            ->orderBy('meter_no')
            ->get();
            
        return response()->json([
            'shared_meters' => $sharedMeters,
            'total_shared_meters' => $sharedMeters->count()
        ]);
    });
      Route::get('/shared-meters/{meter_no}', function($meterNo) {
        $members = DB::table('members')
            ->where('meter_no', $meterNo)
            ->select('member_id', 'account_no', 'fname', 'lname', 'connection_status', 'is_paid', 'prev_balance')
            ->get();
              return response()->json([
            'meter_no' => $meterNo,
            'members' => $members,
            'total_members' => $members->count()
        ]);
    });
    
    // Payment routes
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/payments/{id}', [PaymentController::class, 'processPayment']);
    Route::get('/payments/latest/{id}', [PaymentController::class, 'getLatestPayment']);
    
    // Payment history route
    Route::get('/members/{id}/payment-history', [PaymentController::class, 'getMemberPaymentHistory']);
    
    // Treasurer routes
    Route::get('/treasurer/stats', [TreasurerDashboardController::class, 'getStats']);
    
    // Master List (with billing calculations)
    Route::get('/master-list', [MasterListController::class, 'index']);
    
    // Expenses
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::put('/expenses/{id}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);
    
    // Collections
    Route::get('/collections', [CollectionController::class, 'index']);
    Route::post('/collections', [CollectionController::class, 'store']);
    
    // Payment Transactions (Bank transactions)
    Route::get('/payment-transactions', [PaymentTransactionController::class, 'index']);
    Route::post('/payment-transactions', [PaymentTransactionController::class, 'store']);
    Route::put('/payment-transactions/{id}', [PaymentTransactionController::class, 'update']);
    Route::delete('/payment-transactions/{id}', [PaymentTransactionController::class, 'destroy']);
    
    // President routes
    Route::get('/important-information', [ImportantInformationController::class, 'show']);
    Route::post('/important-information', [ImportantInformationController::class, 'store']);
    Route::put('/important-information/{id}', [ImportantInformationController::class, 'update']);
    
    // User Management
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::get('/roles', [UserController::class, 'getRoles']);

    // OR Booklet Management
    Route::get('/or-booklets', [OrBookletController::class, 'index']);
    Route::get('/or-booklets/available-count', [OrBookletController::class, 'getAvailableCount']);
    Route::get('/or-booklets/next-or-number', [OrBookletController::class, 'getNextOrNumber']);
    Route::post('/or-booklets', [OrBookletController::class, 'store']);
    Route::post('/or-booklets/mark-as-used', [OrBookletController::class, 'markAsUsed']);

    Route::get('/puroks', [PurokController::class, 'index']);
    
    Route::get('/ts-numbers', [TsNumberController::class, 'index']);
    Route::get('/membership-fees', [MemberController::class, 'getMembershipFees']);
    Route::put('/members/{id}', [MemberController::class, 'update']);
    
    Route::get('/water-consumptions', [WaterConsumptionController::class, 'index']);
    Route::post('/water-consumptions', [WaterConsumptionController::class, 'store']);
    
    // Members
    Route::post('/members', [MemberController::class, 'store']);  
    Route::get('/members/by-account/{accountNo}', [MemberController::class, 'getByAccountNumber']);
    

    // Reset endpoint for meters
    Route::get('/reset-meters', function () {
        // Get current time in Philippines
        $now = now()->timezone('Asia/Manila');
        
        // Check if it's the 10th of the month
        if ($now->day === 10) {
            // Check if we haven't reset yet today
            $lastResetKey = 'last_meter_reset_' . $now->format('Y_m');
            $lastReset = \Illuminate\Support\Facades\Cache::get($lastResetKey);
            
            if (!$lastReset) {
                // Perform the reset
                DB::table('members')
                    ->update([
                        'is_read' => 0,
                        'is_paid' => 0
                    ]);
                
                // Mark as reset for this month
                \Illuminate\Support\Facades\Cache::put($lastResetKey, true, now()->addDays(31));
                
                return response()->json(['message' => 'Meters reset successfully']);
            }
                  return response()->json(['message' => 'Reset already performed this month']);
        }
        
        return response()->json(['message' => 'Not time to reset yet']);
    });
      // Dashboard routes for president
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
    Route::get('/dashboard/unpaid-members', [DashboardController::class, 'unpaidMembers']);
    Route::get('/dashboard/monthly-trends', [DashboardController::class, 'monthlyTrends']);
    Route::get('/dashboard/payment-breakdown', [DashboardController::class, 'paymentTypeBreakdown']);
    Route::get('/dashboard/collector-performance', [DashboardController::class, 'collectorPerformance']);
    
    // Billing period & monthly master list (record keeping)    
    Route::post('/billing-periods/generate', [MonthlyArchiveController::class, 'generate']);
    Route::get('/billing-periods', function () {
        return DB::table('billing_periods')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    });    Route::get('/monthly-master-list/{billingPeriod}', function ($billingPeriod) {
        return DB::table('monthly_master_lists')
            ->where('billing_period_id', $billingPeriod)
            ->orderBy('ts_id')
            ->orderBy('account_no')
            ->get();
    });
    
    Route::put('/monthly-master-list/{monthlyMasterListId}', function ($monthlyMasterListId) {
        $request = request();
        DB::table('monthly_master_lists')
            ->where('id', $monthlyMasterListId)
            ->update([
                'damage_charges' => $request->input('damage_charges', 0),
                'updated_at' => now(),
            ]);
        
        return response()->json(['message' => 'Updated successfully']);
    });
    
    Route::get('/or-booklet', [OrBookletController::class, 'index']);
    
});

Route::middleware('validate.sync.token')->group(function () {
    
    Route::post('/water-consumptions/sync', [WaterConsumptionController::class, 'sync']);
    Route::get('/sync-data', function () {
        try {
            $dbPath = config('database.connections.sqlite.database');
            
            // Handle relative path by making it absolute
            if (!str_starts_with($dbPath, '/') && !str_contains($dbPath, ':')) {
                $dbPath = base_path($dbPath);
            }
            
            if (!file_exists($dbPath)) {
                return response()->json([
                    'error' => 'Database file not found',
                    'path' => $dbPath
                ], 404);
            }
            
            if (!is_readable($dbPath)) {
                return response()->json([
                    'error' => 'Database file is not readable',
                    'path' => $dbPath
                ], 403);
            }
            
            return response()->file($dbPath, [
                'Content-Type' => 'application/x-sqlite3',
                'Content-Disposition' => 'attachment; filename="charmafa.db"',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);        }    });
    Route::post('/water-consumptions/update-sync-status', [WaterConsumptionController::class, 'updateSyncStatus']);
    
});





