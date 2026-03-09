<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WaterConsumptionController extends Controller
{
    /**
     * Sync water consumptions from mobile app
     * Updated to only update existing records based on member_Id
     * Also sets is_paid = 0 for synced members
     */
    public function sync(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'consumptions' => 'required|array',
                'consumptions.*.member_Id' => 'required|integer|exists:members,member_id',
                'consumptions.*.prev_CUM_consumption' => 'nullable|integer',
                'consumptions.*.present_CUM_consumption' => 'nullable|integer',
                'consumptions.*.prev_meter_reading' => 'nullable|integer',
                'consumptions.*.present_meter_reading' => 'nullable|integer',
                'consumptions.*.reading_date' => 'nullable|string',
                'consumptions.*.processed_by' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $consumptions = $request->input('consumptions');
            $syncedCount = 0;
            $updatedCount = 0;
            $createdCount = 0;
            $syncedMemberIds = []; // Track which members were synced

            DB::beginTransaction();

            try {
                foreach ($consumptions as $consumption) {
                    // Find existing consumption by member_Id ONLY (not including reading_date)
                    $existingConsumption = DB::table('water_consumptions')
                        ->where('member_Id', $consumption['member_Id'])
                        ->orderBy('id', 'desc')  // Get the most recent record
                        ->first();

                    if ($existingConsumption) {
                        // Update existing record
                        DB::table('water_consumptions')
                            ->where('id', $existingConsumption->id)
                            ->update([
                                'prev_CUM_consumption' => $consumption['prev_CUM_consumption'],
                                'present_CUM_consumption' => $consumption['present_CUM_consumption'],
                                'prev_meter_reading' => $consumption['prev_meter_reading'],
                                'present_meter_reading' => $consumption['present_meter_reading'],
                                'others' => $consumption['others'] ?? null,
                                'reading_date' => $consumption['reading_date'],
                                'processed_by' => $consumption['processed_by'] ?? null,
                                'is_synced' => 1,
                            ]);
                        $updatedCount++;
                        
                        Log::info("Updated water consumption for member {$consumption['member_Id']}", [
                            'record_id' => $existingConsumption->id,
                            'reading_date' => $consumption['reading_date']
                        ]);
                    } else {
                        // Insert new record (only if member has no record at all)
                        DB::table('water_consumptions')->insert([
                            'member_Id' => $consumption['member_Id'],
                            'prev_CUM_consumption' => $consumption['prev_CUM_consumption'],
                            'present_CUM_consumption' => $consumption['present_CUM_consumption'],
                            'prev_meter_reading' => $consumption['prev_meter_reading'],
                            'present_meter_reading' => $consumption['present_meter_reading'],
                            'others' => $consumption['others'] ?? null,
                            'reading_date' => $consumption['reading_date'],
                            'processed_by' => $consumption['processed_by'] ?? null,
                            'is_synced' => 1,
                        ]);
                        $createdCount++;
                        
                        Log::info("Created new water consumption for member {$consumption['member_Id']}", [
                            'reading_date' => $consumption['reading_date']
                        ]);
                    }

                    // Track this member for batch update
                    if (!in_array($consumption['member_Id'], $syncedMemberIds)) {
                        $syncedMemberIds[] = $consumption['member_Id'];
                    }

                    $syncedCount++;
                }

                // Batch update all synced members: set is_read = 1 and is_paid = 0
                if (!empty($syncedMemberIds)) {
                    $membersUpdated = DB::table('members')
                        ->whereIn('member_id', $syncedMemberIds)
                        ->update([
                            'last_reading_date' => DB::raw('(SELECT reading_date FROM water_consumptions WHERE member_Id = members.member_id ORDER BY id DESC LIMIT 1)'),
                            'is_read' => 1,
                            'is_paid' => 0, // Set to unpaid when new reading is synced
                        ]);

                    Log::info("Updated member statuses for synced records", [
                        'member_count' => $membersUpdated,
                        'member_ids' => $syncedMemberIds
                    ]);
                }

                DB::commit();

                Log::info('Water consumptions synced successfully', [
                    'total_synced' => $syncedCount,
                    'updated' => $updatedCount,
                    'created' => $createdCount,
                    'members_updated' => count($syncedMemberIds),
                    'user_id' => $request->user()->admin_id ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully synced {$syncedCount} records (Updated: {$updatedCount}, Created: {$createdCount})",
                    'synced_count' => $syncedCount,
                    'updated_count' => $updatedCount,
                    'created_count' => $createdCount,
                    'members_marked_unpaid' => count($syncedMemberIds)
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Error syncing water consumptions', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync data: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Sync endpoint error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all water consumptions (optional - for debugging)
     */
    public function index(Request $request)
    {
        try {
            $consumptions = DB::table('water_consumptions')
                ->join('members', 'water_consumptions.member_Id', '=', 'members.member_id')
                ->leftJoin('users', 'water_consumptions.processed_by', '=', 'users.admin_id')
                ->select(
                    'water_consumptions.*',
                    'members.meter_no',
                    'members.fname as member_fname',
                    'members.lname as member_lname',
                    DB::raw("CONCAT(users.fname, ' ', users.lname) as processor_name")
                )
                ->orderBy('water_consumptions.reading_date', 'desc')
                ->paginate(50);

            return response()->json($consumptions);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update sync status for records (called after successful sync)
     * Now also updates is_paid status for members
     */
    public function updateSyncStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'record_ids' => 'required|array',
                'member_ids' => 'nullable|array',
                'is_synced' => 'required|integer',
                'is_read' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $recordIds = $request->input('record_ids');
            $memberIds = $request->input('member_ids', []);
            $isSynced = $request->input('is_synced');
            $isRead = $request->input('is_read', 1);

            DB::beginTransaction();

            try {
                // Update water consumption records sync status
                $updatedCount = DB::table('water_consumptions')
                    ->whereIn('id', $recordIds)
                    ->update(['is_synced' => $isSynced]);

                // Update member is_read and is_paid status
                $membersUpdated = 0;
                if (!empty($memberIds)) {
                    $membersUpdated = DB::table('members')
                        ->whereIn('member_id', $memberIds)
                        ->update([
                            'is_read' => $isRead,
                            'is_paid' => 0, // Mark as unpaid when readings are synced
                        ]);
                }

                DB::commit();

                Log::info('Sync status updated', [
                    'records_updated' => $updatedCount,
                    'members_updated' => $membersUpdated,
                    'members_marked_unpaid' => $membersUpdated
                ]);

                return response()->json([
                    'success' => true,
                    'updated_count' => $updatedCount,
                    'members_updated' => $membersUpdated,
                    'message' => 'Sync status updated successfully'
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error updating sync status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating sync status: ' . $e->getMessage()
            ], 500);
        }
    }
}