<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function analytics(Request $request)
    {
        $period = $request->get('period', 'current');
        
        try {
            // Get date range based on period
            $dateRange = $this->getDateRange($period);
            
            // Get total members count
            $totalMembers = Member::count();
            
            // Get active members (connection_status = 1)
            $activeMembers = Member::where('connection_status', 1)->count();
            
            // Get paid members count (members who have paid current bill)
            $paidMembers = Member::where('is_paid', 1)->count();
            
            // Get unpaid members count
            $unpaidMembers = Member::where('is_paid', 0)->count();
            
            // Get disconnected members
            $disconnectedMembers = Member::where('connection_status', 0)->count();
            
            // Get total collected amount for the period
            $totalCollected = Payment::whereBetween('payment_date', $dateRange)->sum('amount_paid');
            
            // Calculate collection rate
            $collectionRate = $totalMembers > 0 ? round(($paidMembers / $totalMembers) * 100, 1) : 0;
            
            // Get monthly collection for current collection period (10th to 9th cycle)
            $monthlyCollection = $this->getCurrentPeriodCollection();
            
            // Calculate balance statistics
            $balanceStats = Member::where('prev_balance', '>', 0)
                ->selectRaw('
                    SUM(prev_balance) as total_outstanding,
                    AVG(prev_balance) as average_balance,
                    MAX(prev_balance) as highest_balance
                ')
                ->first();
            
            return response()->json([
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'paid_members' => $paidMembers,
                'unpaid_members' => $unpaidMembers,
                'disconnected_members' => $disconnectedMembers,
                'total_collected' => $totalCollected,
                'collection_rate' => $collectionRate,
                'monthly_collection' => $monthlyCollection,
                'total_outstanding_balance' => $balanceStats->total_outstanding ?? 0,
                'average_balance' => $balanceStats->average_balance ?? 0,
                'highest_balance' => $balanceStats->highest_balance ?? 0,
                'period' => $period,
                'date_range' => $dateRange
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Dashboard analytics error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load analytics data'], 500);
        }
    }    public function unpaidMembers(Request $request)
    {
        try {
            $unpaidMembers = Member::select([
                'member_id',
                'account_no', 
                'fname',
                'lname',
                'prev_balance',
                'connection_status',
                'last_reading_date'
            ])
            ->where('is_paid', 0)
            ->orderBy('prev_balance', 'desc')
            ->get();
            
            // Add last payment date manually without relationships to avoid errors
            $unpaidMembers = $unpaidMembers->map(function($member) {
                // Get last payment manually
                $lastPayment = \App\Models\Payment::where('member_id', $member->member_id)
                    ->latest('payment_date')
                    ->first();
                    
                $member->last_payment_date = $lastPayment ? $lastPayment->payment_date : null;
                return $member;
            });
            
            return response()->json($unpaidMembers);
            
        } catch (\Exception $e) {
            \Log::error('Unpaid members error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load unpaid members'], 500);
        }
    }
    
    private function getCurrentPeriodCollection()
    {
        $now = Carbon::now();
        $currentDay = $now->day;
        
        // Collection period runs from 10th to 9th of next month
        if ($currentDay >= 10) {
            // Current period: 10th of current month to 9th of next month
            $periodStart = $now->copy()->day(10)->startOfDay();
            $periodEnd = $now->copy()->addMonth()->day(9)->endOfDay();
        } else {
            // Current period: 10th of last month to 9th of current month
            $periodStart = $now->copy()->subMonth()->day(10)->startOfDay();
            $periodEnd = $now->copy()->day(9)->endOfDay();
        }
        
        // Get total collection for current period
        return Payment::whereBetween('payment_date', [$periodStart, $periodEnd])
            ->sum('amount_paid');
    }    public function monthlyTrends(Request $request)
    {
        try {
            $months = $request->get('months', 12); // Default to 12 months
            
            // Get collection periods for the specified number of months
            $trends = [];
            $now = Carbon::now();
            
            for ($i = 0; $i < $months; $i++) {
                $targetDate = $now->copy()->subMonths($i);
                $currentDay = $targetDate->day;
                
                // Determine collection period (10th to 9th cycle)
                if ($currentDay >= 10) {
                    // Period: 10th of current month to 9th of next month
                    $periodStart = $targetDate->copy()->day(10)->startOfDay();
                    $periodEnd = $targetDate->copy()->addMonth()->day(9)->endOfDay();
                    $periodName = $targetDate->format('Y-m'); // Use the starting month
                } else {
                    // Period: 10th of last month to 9th of current month  
                    $periodStart = $targetDate->copy()->subMonth()->day(10)->startOfDay();
                    $periodEnd = $targetDate->copy()->day(9)->endOfDay();
                    $periodName = $targetDate->copy()->subMonth()->format('Y-m'); // Use the starting month
                }
                
                // Check if we already have this period to avoid duplicates
                $existingPeriod = collect($trends)->where('month', $periodName)->first();
                if ($existingPeriod) {
                    continue;
                }
                
                // Get payments for this collection period
                $totalAmount = Payment::whereBetween('payment_date', [$periodStart, $periodEnd])
                    ->sum('amount_paid');
                
                $paymentCount = Payment::whereBetween('payment_date', [$periodStart, $periodEnd])
                    ->count();
                
                $trends[] = [
                    'month' => $periodName,
                    'total_amount' => $totalAmount,
                    'payment_count' => $paymentCount,
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d')
                ];
            }
            
            // Sort by month and remove duplicates
            $trends = collect($trends)
                ->unique('month')
                ->sortBy('month')
                ->values()
                ->toArray();
            
            return response()->json($trends);
            
        } catch (\Exception $e) {
            \Log::error('Monthly trends error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load monthly trends'], 500);
        }
    }
    
    public function paymentTypeBreakdown(Request $request)
    {
        try {
            $period = $request->get('period', 'current');
            $dateRange = $this->getDateRange($period);
            
            $breakdown = Payment::whereBetween('payment_date', $dateRange)
                ->select(
                    DB::raw('
                        CASE 
                            WHEN reference LIKE "BILL-%" THEN "Regular Payment"
                            WHEN reference LIKE "RECON-%" THEN "Reconnection Payment"
                            WHEN reference LIKE "BAL-%" THEN "Balance Payment"
                            ELSE "Other"
                        END as payment_type
                    '),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount_paid) as total_amount')
                )
                ->groupBy('payment_type')
                ->get();
            
            return response()->json($breakdown);
            
        } catch (\Exception $e) {
            \Log::error('Payment breakdown error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load payment breakdown'], 500);
        }
    }
    
    public function collectorPerformance(Request $request)
    {
        try {
            $period = $request->get('period', 'current');
            $dateRange = $this->getDateRange($period);
            
            $performance = Payment::with('collector:admin_id,fname,lname')
                ->whereBetween('payment_date', $dateRange)
                ->select(
                    'collected_by',
                    DB::raw('COUNT(*) as payment_count'),
                    DB::raw('SUM(amount_paid) as total_collected')
                )
                ->groupBy('collected_by')
                ->orderBy('total_collected', 'desc')
                ->get();
            
            $performance = $performance->map(function($item) {
                return [
                    'collector_id' => $item->collected_by,
                    'collector_name' => $item->collector ? 
                        $item->collector->fname . ' ' . $item->collector->lname : 
                        'Unknown',
                    'payment_count' => $item->payment_count,
                    'total_collected' => $item->total_collected
                ];
            });
            
            return response()->json($performance);
            
        } catch (\Exception $e) {
            \Log::error('Collector performance error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load collector performance'], 500);
        }
    }
    
    private function getDateRange($period)
    {
        $now = Carbon::now();
        
        switch($period) {
            case 'current':
                return [$now->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString()];
            
            case 'last':
                $lastMonth = $now->subMonth();
                return [$lastMonth->startOfMonth()->toDateString(), $lastMonth->endOfMonth()->toDateString()];
            
            case 'quarter':
                return [$now->subMonths(3)->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString()];
            
            case 'year':
                return [$now->startOfYear()->toDateString(), $now->endOfYear()->toDateString()];
            
            default:
                return [$now->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString()];
        }
    }
}
