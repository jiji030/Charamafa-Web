<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{    public function index(Request $request)
    {
        try {
            \Log::info('Collections index called');

            // First, sync collections from payments table
            $this->syncCollectionsFromPayments();
            
            // Check if limit is requested (for dashboard)
            $limit = $request->get('limit');

            // Now fetch from collections table with collector names
            $query = DB::table('collections')
                ->leftJoin('users', 'collections.collector_id', '=', 'users.admin_id')
                ->select(
                    'collections.*',
                    DB::raw("users.fname || ' ' || COALESCE(users.mname || ' ', '') || users.lname || COALESCE(' ' || users.suffix, '') as collector_name")
                )
                ->orderBy('collections.year', 'desc')
                ->orderBy('collections.month', 'desc');
            
            // Apply limit if requested
            if ($limit) {
                $query->limit((int)$limit);
            }
            
            $collections = $query->get();

            // Format the results
            $formattedCollections = [];
            
            foreach ($collections as $collection) {
                // Format month display (e.g., "November 2025")
                $monthYear = $collection->month . ' ' . $collection->year;
                
                $formattedCollections[] = [
                    'collection_id' => $collection->collection_id,
                    'month' => $monthYear,
                    'billing_month' => sprintf('%04d-%02d', $collection->year, $this->getMonthNumber($collection->month)),
                    'total_collection' => (float) $collection->total_collection,
                    'collector_id' => $collection->collector_id,
                    'collector_name' => $collection->collector_name ?: 'Unknown Collector'
                ];
            }

            \Log::info('Formatted collections: ' . count($formattedCollections));

            return response()->json($formattedCollections);

        } catch (\Exception $e) {
            \Log::error('Collections index error: ' . $e->getMessage());
            \Log::error('Line: ' . $e->getLine());
            \Log::error('File: ' . $e->getFile());
            
            return response()->json([
                'message' => 'Failed to load collections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync collections table from payments table
     * Groups payments by billing period (10th to 9th)
     */
    private function syncCollectionsFromPayments()
    {
        try {
            // Get all payments (using collected_by column)
            $payments = DB::table('payments')
                ->select('payment_id', 'payment_date', 'amount_paid', 'collected_by')
                ->whereNotNull('payment_date')
                ->whereNotNull('amount_paid')
                ->get();

            if ($payments->isEmpty()) {
                \Log::info('No payments to sync');
                return;
            }

            // Group payments by billing month (not by collector)
            $grouped = [];
            
            foreach ($payments as $payment) {
                try {
                    $paymentDate = new \DateTime($payment->payment_date);
                    $day = (int) $paymentDate->format('d');
                    
                    // Determine billing month (10th to 9th cycle)
                    if ($day >= 10) {
                        // Payment on or after 10th belongs to current month
                        $billingDate = $paymentDate;
                    } else {
                        // Payment before 10th belongs to previous month
                        $billingDate = (clone $paymentDate)->modify('-1 month');
                    }
                    
                    $month = $billingDate->format('F'); // e.g., "November"
                    $year = (int) $billingDate->format('Y');
                    
                    $key = $year . '-' . $month;
                    
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'month' => $month,
                            'year' => $year,
                            'total_collection' => 0,
                            'collector_id' => $payment->collected_by ?? null
                        ];
                    }
                    
                    $grouped[$key]['total_collection'] += (float) $payment->amount_paid;
                    
                    // Keep track of last collector who made a payment for this period
                    if ($payment->collected_by) {
                        $grouped[$key]['collector_id'] = $payment->collected_by;
                    }
                    
                } catch (\Exception $e) {
                    \Log::warning('Error processing payment: ' . $e->getMessage());
                    continue;
                }
            }

            \Log::info('Grouped payments: ' . count($grouped));

            // Insert or update collections table
            foreach ($grouped as $data) {
                DB::table('collections')->updateOrInsert(
                    [
                        'month' => $data['month'],
                        'year' => $data['year']
                    ],
                    [
                        'total_collection' => round($data['total_collection'], 2),
                        'collector_id' => $data['collector_id'],
                        'collection_date' => now(),
                        'created_at' => now()
                    ]
                );
            }

            \Log::info('Collections synced successfully');

        } catch (\Exception $e) {
            \Log::error('Error syncing collections: ' . $e->getMessage());
        }
    }

    /**
     * Get month number from month name
     */
    private function getMonthNumber($monthName)
    {
        $months = [
            'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
            'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
            'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
        ];
        
        return $months[$monthName] ?? 1;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|string',
            'year' => 'required|integer',
            'total_collection' => 'required|numeric|min:0',
            'collector_id' => 'nullable|integer',
            'collection_date' => 'nullable|date'
        ]);

        try {
            $collectionId = DB::table('collections')->insertGetId([
                'month' => $validated['month'],
                'year' => $validated['year'],
                'total_collection' => $validated['total_collection'],
                'collector_id' => $validated['collector_id'] ?? auth()->id(),
                'collection_date' => $validated['collection_date'] ?? now(),
                'created_at' => now()
            ]);

            return response()->json([
                'message' => 'Collection created successfully',
                'collection_id' => $collectionId
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create collection',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}