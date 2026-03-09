<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisconnectionController extends Controller
{
    /**
     * Disconnect a member and all members sharing the same meter
     */
    public function disconnect(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        $member = Member::findOrFail($id);

        // Check if member is already disconnected
        if ($member->connection_status == 0) {
            return response()->json([
                'message' => 'Member is already disconnected',
                'shared_meter_info' => [
                    'meter_no' => $member->meter_no,
                    'members_affected' => $member->sharedMeterMembers()->count()
                ]
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get shared meter members before disconnection
            $sharedMembers = $member->sharedMeterMembers();

            // Update connection status for all members sharing the same meter
            $member->updateSharedMeterConnectionStatus(0); // 0 = disconnected

            // Create disconnection log
            try {
                // Create disconnection_logs table if it doesn't exist
                DB::statement('CREATE TABLE IF NOT EXISTS disconnection_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    member_id INTEGER NOT NULL,
                    meter_no TEXT,
                    reason TEXT NOT NULL,
                    notes TEXT,
                    disconnected_by INTEGER,
                    disconnected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (member_id) REFERENCES members (member_id),
                    FOREIGN KEY (disconnected_by) REFERENCES users (id)
                )');

                // Log disconnection for each affected member
                foreach ($sharedMembers as $sharedMember) {
                    DB::table('disconnection_logs')->insert([
                        'member_id' => $sharedMember->member_id,
                        'meter_no' => $sharedMember->meter_no,
                        'reason' => $request->reason,
                        'notes' => $request->notes,
                        'disconnected_by' => auth()->id() ?? 1,
                        'disconnected_at' => now()
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not create disconnection log: ' . $e->getMessage());
            }

            // Log shared meter disconnection
            \Log::info('Shared meter disconnection processed', [
                'disconnecting_member' => $member->account_no,
                'meter_no' => $member->meter_no,
                'affected_members' => $sharedMembers->pluck('account_no')->toArray(),
                'reason' => $request->reason,
                'disconnected_by' => auth()->id()
            ]);

            DB::commit();

            // Get updated shared members info
            $updatedSharedMembers = $member->fresh()->sharedMeterMembers();

            return response()->json([
                'message' => 'Disconnection processed successfully',
                'member' => $member->fresh(),
                'shared_meter_info' => [
                    'meter_no' => $member->meter_no,
                    'total_members_affected' => $updatedSharedMembers->count(),
                    'affected_members' => $updatedSharedMembers->map(function($m) {
                        return [
                            'member_id' => $m->member_id,
                            'account_no' => $m->account_no,
                            'full_name' => $m->full_name,
                            'connection_status' => $m->connection_status,
                            'prev_balance' => $m->prev_balance
                        ];
                    })
                ],
                'disconnection_details' => [
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                    'disconnected_at' => now(),
                    'disconnected_by' => auth()->user()->name ?? 'System'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Disconnection failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Disconnection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get disconnection history for a member
     */
    public function getDisconnectionHistory($id)
    {
        $member = Member::findOrFail($id);

        try {
            $disconnectionLogs = DB::table('disconnection_logs')
                ->leftJoin('users', 'disconnection_logs.disconnected_by', '=', 'users.id')
                ->where('disconnection_logs.member_id', $id)
                ->select([
                    'disconnection_logs.*',
                    'users.name as disconnected_by_name'
                ])
                ->orderBy('disconnected_at', 'desc')
                ->get();

            return response()->json([
                'member' => $member,
                'disconnection_history' => $disconnectionLogs,
                'shared_meter_info' => [
                    'meter_no' => $member->meter_no,
                    'shared_members' => $member->sharedMeterMembers()->map(function($m) {
                        return [
                            'member_id' => $m->member_id,
                            'account_no' => $m->account_no,
                            'full_name' => $m->full_name,
                            'connection_status' => $m->connection_status
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'member' => $member,
                'disconnection_history' => [],
                'message' => 'Disconnection history table does not exist yet'
            ]);
        }
    }

    /**
     * Get all disconnected members with shared meter information
     */
    public function getDisconnectedMembers(Request $request)
    {
        $query = Member::where('connection_status', 0)
                      ->with(['purok', 'tsNumber']);

        // Apply filters
        if ($request->has('purok_id') && $request->purok_id) {
            $query->where('purok_id', $request->purok_id);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                  ->orWhere('lname', 'like', "%{$search}%")
                  ->orWhere('account_no', 'like', "%{$search}%")
                  ->orWhere('meter_no', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('fname')->get();

        // Group by meter number and add shared meter information
        $groupedByMeter = $members->groupBy('meter_no')->map(function($meterMembers, $meterNo) {
            return [
                'meter_no' => $meterNo,
                'members_count' => $meterMembers->count(),
                'members' => $meterMembers->map(function($member) {
                    return [
                        'member_id' => $member->member_id,
                        'account_no' => $member->account_no,
                        'full_name' => $member->full_name,
                        'purok_name' => $member->purok->purok_name ?? 'N/A',
                        'prev_balance' => $member->prev_balance,
                        'connection_status' => $member->connection_status
                    ];
                })
            ];
        });

        return response()->json([
            'disconnected_members' => $members->map(function($member) {
                $sharedMembers = $member->sharedMeterMembers();
                return [
                    'member_id' => $member->member_id,
                    'account_no' => $member->account_no,
                    'full_name' => $member->full_name,
                    'purok_name' => $member->purok->purok_name ?? 'N/A',
                    'meter_no' => $member->meter_no,
                    'prev_balance' => $member->prev_balance,
                    'connection_status' => $member->connection_status,
                    'shared_meter_count' => $sharedMembers->count(),
                    'has_shared_meter' => $sharedMembers->count() > 1
                ];
            }),
            'grouped_by_meter' => $groupedByMeter,
            'total_disconnected' => $members->count(),
            'total_meters_affected' => $groupedByMeter->count()
        ]);
    }
}
