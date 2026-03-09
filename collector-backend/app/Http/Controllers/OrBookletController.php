<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrBookletController extends Controller
{
    /**
     * Get all OR booklets with usage statistics
     */
    public function index()
    {
        try {
            $booklets = DB::table('or_booklets as ob')
                ->leftJoin('users as u', 'ob.created_by', '=', 'u.admin_id')
                ->select(
                    'ob.*',
                    'u.username as created_by_name',
                    DB::raw('(SELECT COUNT(*) FROM or_numbers WHERE booklet_id = ob.booklet_id) as total_numbers'),
                    DB::raw('(SELECT COUNT(*) FROM or_numbers WHERE booklet_id = ob.booklet_id AND is_used = 1) as used_numbers'),
                    DB::raw('(SELECT COUNT(*) FROM or_numbers WHERE booklet_id = ob.booklet_id AND is_used = 0) as available_numbers')
                )
                ->orderBy('ob.created_at', 'desc')
                ->get();

            return response()->json($booklets);
        } catch (\Exception $e) {
            Log::error('Error fetching OR booklets: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch OR booklets'], 500);
        }
    }

    /**
     * Get current available OR numbers count
     */
    public function getAvailableCount()
    {
        try {
            $count = DB::table('or_numbers')
                ->where('is_used', 0)
                ->count();

            return response()->json(['available_count' => $count]);
        } catch (\Exception $e) {
            Log::error('Error fetching available OR count: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch available OR count'], 500);
        }
    }

    /**
     * Get next available OR number
     */
    public function getNextOrNumber()
    {
        try {
            $nextOr = DB::table('or_numbers')
                ->where('is_used', 0)
                ->orderBy('or_number', 'asc')
                ->first();

            if (!$nextOr) {
                return response()->json(['error' => 'No OR numbers available'], 404);
            }

            return response()->json($nextOr);
        } catch (\Exception $e) {
            Log::error('Error fetching next OR number: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch next OR number'], 500);
        }
    }

    /**
     * Create new OR booklet and generate OR numbers
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_number' => 'required|string',
                'end_number' => 'required|string',
                'created_by' => 'required|integer'
            ]);

            // Validate that start is less than end
            $start = intval($validated['start_number']);
            $end = intval($validated['end_number']);

            if ($start >= $end) {
                return response()->json(['error' => 'Start number must be less than end number'], 400);
            }

            DB::beginTransaction();

            // Create booklet
            $bookletId = DB::table('or_booklets')->insertGetId([
                'start_number' => $validated['start_number'],
                'end_number' => $validated['end_number'],
                'created_by' => $validated['created_by'],
                'created_at' => now()
            ]);

            // Generate OR numbers in the range
            $orNumbers = [];
            $padLength = strlen($validated['start_number']);

            for ($i = $start; $i <= $end; $i++) {
                $orNumbers[] = [
                    'or_number' => str_pad($i, $padLength, '0', STR_PAD_LEFT),
                    'booklet_id' => $bookletId,
                    'is_used' => 0,
                    'used_at' => null,
                    'used_by' => null
                ];
            }

            // Insert OR numbers in batches
            foreach (array_chunk($orNumbers, 100) as $chunk) {
                DB::table('or_numbers')->insert($chunk);
            }

            DB::commit();

            Log::info("Created OR booklet {$bookletId} with " . count($orNumbers) . " OR numbers");

            return response()->json([
                'message' => 'OR booklet created successfully',
                'booklet_id' => $bookletId,
                'total_numbers' => count($orNumbers)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating OR booklet: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create OR booklet: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark OR number as used
     */
    public function markAsUsed(Request $request)
    {
        try {
            $validated = $request->validate([
                'or_number' => 'required|string',
                'used_by' => 'required|integer'
            ]);

            $updated = DB::table('or_numbers')
                ->where('or_number', $validated['or_number'])
                ->where('is_used', 0)
                ->update([
                    'is_used' => 1,
                    'used_at' => now(),
                    'used_by' => $validated['used_by']
                ]);

            if (!$updated) {
                return response()->json(['error' => 'OR number not found or already used'], 404);
            }

            return response()->json(['message' => 'OR number marked as used']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error marking OR as used: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to mark OR as used'], 500);
        }
    }
}
