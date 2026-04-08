<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\ImportantInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{    
    public function index(Request $request)
    {
        $query = Member::with(['purok', 'tsNumber'])
            ->select('members.*');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('account_no', 'LIKE', "%{$search}%")
                  ->orWhere('fname', 'LIKE', "%{$search}%")
                  ->orWhere('lname', 'LIKE', "%{$search}%")
                  ->orWhere('meter_no', 'LIKE', "%{$search}%");
            });
        }

        $sortField = $request->get('sort_field', 'member_id');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        return response()->json($query->get());
    }    public function show($id)
    {
        $member = Member::with(['purok', 'tsNumber', 'membershipFee', 'waterConsumptions'])
            ->findOrFail($id);

        // For shared meters, get consumption from any member with the same meter number
        if ($member->meter_no) {
            $latestConsumption = DB::table('water_consumptions as wc')
                ->join('members as m', 'wc.member_Id', '=', 'm.member_id')
                ->where('m.meter_no', $member->meter_no)
                ->where('m.meter_no', '!=', '')
                ->whereNotNull('m.meter_no')
                ->orderBy('wc.reading_date', 'desc')
                ->select('wc.*')
                ->first();
        } else {
            // Individual meter - use member's own consumption
            $latestConsumption = $member->waterConsumptions()
                ->latest('reading_date')
                ->first();
        }

        $info = ImportantInformation::first();
        $bill = $this->calculateBill($member, $latestConsumption, $info);

        return response()->json([
            'member' => $member,
            'latest_consumption' => $latestConsumption,
            'bill_details' => $bill,
            'info' => $info
        ]);
    }    public function disconnect(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $member = Member::findOrFail($id);

        if ($member->connection_status === 0) {
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

            // Log shared meter disconnection
            Log::info('Shared meter disconnection processed', [
                'disconnecting_member' => $member->account_no,
                'meter_no' => $member->meter_no,
                'affected_members' => $sharedMembers->pluck('account_no')->toArray(),
                'reason' => $request->reason,
                'disconnected_by' => auth()->id()
            ]);

            try {
                // Create disconnection log for each affected member
                foreach ($sharedMembers as $sharedMember) {
                    DB::table('disconnection_logs')->insert([
                        'member_id' => $sharedMember->member_id,
                        'meter_no' => $sharedMember->meter_no,
                        'reason' => $request->reason,
                        'disconnected_by' => auth()->id(),
                        'disconnected_at' => now()
                    ]);
                }
            } catch (\Exception $e) {
                // Table doesn't exist yet, log warning but don't fail
                Log::warning('Could not create disconnection log: ' . $e->getMessage());
            }

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
                            'connection_status' => $m->connection_status
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Disconnection failed', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_no' => 'required|string|unique:members,account_no',
            'purok_id' => 'required|integer|exists:puroks,purok_id',
            'ts_Id' => 'required|integer|exists:ts_numbers,ts_Id',
            'meter_no' => 'required|string|max:255',
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'barangay' => 'required|string|max:255',
            'municipality' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'sex' => 'required|in:Male,Female',
            'zip_code' => 'nullable|string|max:10',
            'region' => 'nullable|string|max:255',
            'place_of_birth' => 'nullable|string|max:255',
            'civil_status' => 'nullable|in:Single,Married,Widowed,Separated',
            'mobile_no' => 'nullable|string|max:20',
            'religion' => 'nullable|string|max:100',
            'ethnicity' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:100',
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'occupation' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'education_attainment' => 'nullable|in:Elementary,High School,Vocational,College,Graduate',
            'school_address' => 'nullable|string|max:500',
            'course' => 'nullable|string|max:255',
            'year_graduated' => 'nullable|string|max:4',
            'spouse_fname' => 'nullable|string|max:255',
            'spouse_mname' => 'nullable|string|max:255',
            'spouse_lname' => 'nullable|string|max:255',
            'spouse_suffix' => 'nullable|string|max:10',
            'spouse_date_of_birth' => 'nullable|date',
            'spouse_address' => 'nullable|string|max:500',
            'spouse_ethnicity' => 'nullable|string|max:100',
            'spouse_occupation' => 'nullable|string|max:255',
            'spouse_phone_no' => 'nullable|string|max:20',
            'government_type_id' => 'nullable|string|max:50',
            'government_no' => 'nullable|string|max:100',
            'membership_fee_id' => 'required|integer|exists:membership_fees,membership_fee_id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
        ]);

        $photoName = null;
        $photoPath = null;
        
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoName = time() . '_' . $validated['account_no'] . '.' . $photo->getClientOriginalExtension();
            $destinationPath = base_path('../collector-frontend/public/pictures');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $photo->move($destinationPath, $photoName);
            $photoPath = '/pictures/' . $photoName;
        }

        $validated['connection_status'] = 1;
        $validated['is_approved'] = 0;
        $validated['prev_balance'] = 0;
        $validated['registration_date'] = now();
        $validated['photo_name'] = $photoName;
        $validated['photo_path'] = $photoPath;
        
        unset($validated['photo']);        try {
            DB::beginTransaction();
            
            // Insert the new member
            $memberId = DB::table('members')->insertGetId($validated);
            
            // Check if another member with the same meter number exists
            $meterNo = $validated['meter_no'];
            if ($meterNo) {
                $existingMemberWithSameMeter = DB::table('members')
                    ->where('meter_no', $meterNo)
                    ->where('member_id', '!=', $memberId)
                    ->where('meter_no', '!=', '')
                    ->whereNotNull('meter_no')
                    ->first();
                
                if ($existingMemberWithSameMeter) {
                    Log::info('Found existing member with same meter, copying consumption records', [
                        'new_member_id' => $memberId,
                        'existing_member_id' => $existingMemberWithSameMeter->member_id,
                        'meter_no' => $meterNo
                    ]);
                    
                    // Copy all water consumption records from the existing member
                    $consumptionRecords = DB::table('water_consumptions')
                        ->where('member_Id', $existingMemberWithSameMeter->member_id)
                        ->get();
                    
                    foreach ($consumptionRecords as $record) {
                        DB::table('water_consumptions')->insert([
                            'member_Id' => $memberId,
                            'reading_date' => $record->reading_date,
                            'prev_CUM_consumption' => $record->prev_CUM_consumption,
                            'present_CUM_consumption' => $record->present_CUM_consumption,
                            'prev_meter_reading' => $record->prev_meter_reading,
                            'present_meter_reading' => $record->present_meter_reading,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    
                    // Also copy the same payment status and balance for shared meter
                    DB::table('members')
                        ->where('member_id', $memberId)
                        ->update([
                            'is_paid' => $existingMemberWithSameMeter->is_paid,
                            'prev_balance' => $existingMemberWithSameMeter->prev_balance,
                            'is_read' => $existingMemberWithSameMeter->is_read
                        ]);
                    
                    Log::info('Successfully copied consumption records and status', [
                        'new_member_id' => $memberId,
                        'consumption_records_copied' => count($consumptionRecords),
                        'is_paid' => $existingMemberWithSameMeter->is_paid,
                        'prev_balance' => $existingMemberWithSameMeter->prev_balance
                    ]);
                }
            }
            
            DB::commit();

            return response()->json([
                'message' => 'Member created successfully',
                'member_id' => $memberId,
                'photo_path' => $photoPath,
                'shared_meter_info' => $existingMemberWithSameMeter ? [
                    'copied_from_member_id' => $existingMemberWithSameMeter->member_id,
                    'consumption_records_copied' => count($consumptionRecords ?? [])
                ] : null
            ], 201);        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($photoName && file_exists($destinationPath . '/' . $photoName)) {
                unlink($destinationPath . '/' . $photoName);
            }
            
            // Check for unique constraint violation on account_no
            if (strpos($e->getMessage(), 'UNIQUE constraint failed: members.account_no') !== false) {
                Log::warning('Attempt to create member with duplicate account number', [
                    'account_no' => $validated['account_no'],
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'message' => 'Account number already exists',
                    'error' => 'The account number "' . $validated['account_no'] . '" is already in use. Please choose a different account number.'
                ], 422);
            }
            
            Log::error('Failed to create member with shared meter logic', [
                'error' => $e->getMessage(),
                'meter_no' => $validated['meter_no'] ?? 'unknown'
            ]);
            
            return response()->json([
                'message' => 'Failed to create member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMembershipFees()
    {
        $fees = DB::table('membership_fees')
            ->orderBy('membership_fee_id', 'asc')
            ->get();
        
        return response()->json($fees);
    }

    public function update(Request $request, $id)
{
    Log::info('Update member request', [
        'member_id' => $id,
        'has_photo' => $request->hasFile('photo'),
        'method_override' => $request->input('_method'),
        'sex_value' => $request->input('sex')
    ]);

    $member = Member::findOrFail($id);        try {
        $validated = $request->validate([
            'account_no' => 'required|string|unique:members,account_no,' . $id . ',member_id',
            'purok_id' => 'required|integer|exists:puroks,purok_id',
            'ts_Id' => 'required|integer|exists:ts_numbers,ts_Id',
            'meter_no' => 'required|string|max:255',
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'barangay' => 'required|string|max:255',
            'municipality' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'sex' => 'required|in:Male,Female',
            'zip_code' => 'nullable|string|max:10',
            'region' => 'nullable|string|max:255',
            'place_of_birth' => 'nullable|string|max:255',
            'civil_status' => 'nullable|in:Single,Married,Widowed,Separated',
            'mobile_no' => 'nullable|string|max:20',
            'religion' => 'nullable|string|max:100',
            'ethnicity' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:100',
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'occupation' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'education_attainment' => 'nullable|in:Elementary,High School,Vocational,College,Graduate',
            'school_address' => 'nullable|string|max:500',
            'course' => 'nullable|string|max:255',
            'year_graduated' => 'nullable|string|max:4',
            'spouse_fname' => 'nullable|string|max:255',
            'spouse_mname' => 'nullable|string|max:255',
            'spouse_lname' => 'nullable|string|max:255',
            'spouse_suffix' => 'nullable|string|max:10',
            'spouse_date_of_birth' => 'nullable|date',
            'spouse_address' => 'nullable|string|max:500',
            'spouse_ethnicity' => 'nullable|string|max:100',
            'spouse_occupation' => 'nullable|string|max:255',
            'spouse_phone_no' => 'nullable|string|max:20',
            'government_type_id' => 'nullable|string|max:50',
            'government_no' => 'nullable|string|max:100',            'membership_fee_id' => 'required|integer|exists:membership_fees,membership_fee_id',
            'prev_balance' => 'nullable|numeric',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'remove_photo' => 'nullable|boolean'
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed for member update', [
            'member_id' => $id,
            'validation_errors' => $e->errors(),
            'request_data' => $request->except(['photo'])
        ]);
        
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    }    // Define fields that should be converted to null if empty
    $nullableFields = [
        'mname', 'suffix', 'zip_code', 'region', 'place_of_birth', 'civil_status',
        'mobile_no', 'religion', 'ethnicity', 'language', 'height', 'weight',
        'occupation', 'company_address', 'education_attainment', 'school_address',
        'course', 'year_graduated', 'spouse_fname', 'spouse_mname', 'spouse_lname',        'spouse_suffix', 'spouse_date_of_birth', 'spouse_address', 'spouse_ethnicity',
        'spouse_occupation', 'spouse_phone_no', 'government_type_id', 'government_no',
        'prev_balance'
    ];

    // Convert empty strings to null for nullable fields
    foreach ($nullableFields as $field) {
        if (isset($validated[$field]) && ($validated[$field] === '' || $validated[$field] === 'null')) {
            $validated[$field] = null;
        }
    }    // Handle numeric fields specifically
    $numericFields = ['height', 'weight', 'prev_balance'];
    foreach ($numericFields as $field) {
        if (isset($validated[$field]) && $validated[$field] !== null) {
            $validated[$field] = floatval($validated[$field]);
        }
    }

    // Handle photo removal
    if ($request->input('remove_photo') == '1' || $request->input('remove_photo') === true) {
        if ($member->photo_name) {
            $oldPhotoPath = base_path('../collector-frontend/public/pictures/' . $member->photo_name);
            if (file_exists($oldPhotoPath)) {
                @unlink($oldPhotoPath);
            }
        }
        $validated['photo_name'] = null;
        $validated['photo_path'] = null;
    }
    // Handle new photo upload
    elseif ($request->hasFile('photo')) {
        // Delete old photo if exists
        if ($member->photo_name) {
            $oldPhotoPath = base_path('../collector-frontend/public/pictures/' . $member->photo_name);
            if (file_exists($oldPhotoPath)) {
                @unlink($oldPhotoPath);
            }
        }

        $photo = $request->file('photo');
        $photoName = time() . '_' . $validated['account_no'] . '.' . $photo->getClientOriginalExtension();
        $destinationPath = base_path('../collector-frontend/public/pictures');

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $photo->move($destinationPath, $photoName);
        
        $validated['photo_name'] = $photoName;
        $validated['photo_path'] = '/pictures/' . $photoName;
    }

    // Set update date
    $validated['update_date'] = now();
    
    // Remove photo file from validated data
    unset($validated['photo']);
    unset($validated['remove_photo']);

    try {
        $member->update($validated);

        Log::info('Member updated successfully', [
            'member_id' => $id,
            'updated_fields' => array_keys($validated)
        ]);

        return response()->json([
            'message' => 'Member updated successfully',
            'member' => $member->fresh()
        ]);
    } catch (\Exception $e) {
        Log::error('Member update failed', [
            'member_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Failed to update member',
            'error' => $e->getMessage()
        ], 500);
    }
}
    
    public function destroy($id)
    {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }
        
        if ($member->photo_path && $member->photo_name) {
            $photoFile = base_path('../collector-frontend/public/pictures/' . $member->photo_name);
            if (file_exists($photoFile)) {
                @unlink($photoFile);
            }
        }
        
        $member->delete();
        return response()->json(['message' => 'Member deleted successfully']);
    }        private function calculateBill($member, $consumption, $info)
    {
        $prevBalance = $member->prev_balance ?? 0;        // If there's no consumption record and member has a previous balance,
        // they should only pay the balance (no new bill generation)
        if (!$consumption && $prevBalance > 0) {
            $damageCharges = $member->damage_charges ?? 0; // Show actual damage charges for transparency
            return [
                'excess_minimum_CUM' => 0,
                'excess_charge' => 0,
                'minimum_amount' => 0,
                'electricity_consumption' => 0,
                'connector_damage' => 0,
                'generator_consumption' => 0,
                'damages' => 0,
                'damage_charges' => $damageCharges, // Show actual damage charges for transparency
                'others' => 0,
                'business_permit' => 0,
                'penalty' => 0,
                'charges' => 0,
                'prev_balance' => $prevBalance,
                'vat' => 0,
                'total_bill' => $prevBalance,
                'is_balance_payment' => true
            ];
        }
        
        $freeCumPerMonth = $info->free_CUM_per_month ?? 5;
        $presentConsumption = $consumption ? $consumption->present_CUM_consumption : 0;
        
        $excessMinimumCUM = max(0, $presentConsumption - $freeCumPerMonth);
        
        $electricityConsumption = $info->electricity_consumption ?? 0;
        $connectorDamage = $info->connector_damage_with_unknown_person ?? 0;
        $generatorConsumption = $info->generator_consumption ?? 0;
        $damages = $info->lossdamage_and_other_charges ?? 0;
        $others = 0;
        $businessPermit = 0;
        $penalty = 0;
        $charges = 0;        // Check if member has made a payment for the current billing period
        $hasCurrentPeriodPayment = $this->hasCurrentPeriodPayment($member->member_id, $consumption);
        
        Log::info('calculateBill decision point', [
            'member_id' => $member->member_id,
            'has_current_period_payment' => $hasCurrentPeriodPayment,
            'prev_balance' => $prevBalance,
            'will_show_balance_only' => ($hasCurrentPeriodPayment && $prevBalance > 0)
        ]);
          if ($hasCurrentPeriodPayment && $prevBalance > 0) {
            // If member has already made a payment this period and has remaining balance,
            // they should only pay the remaining balance (allows multiple payments per month)
            Log::info('Returning BALANCE PAYMENT ONLY (multiple payments per month allowed)', [
                'member_id' => $member->member_id,
                'balance_amount' => $prevBalance,
                'allows_multiple_payments' => true
            ]);            
            $damageCharges = $member->damage_charges ?? 0; // Show actual damage charges for transparency
            return [
                'excess_minimum_CUM' => 0,
                'excess_charge' => 0,
                'minimum_amount' => 0,
                'electricity_consumption' => 0,
                'connector_damage' => 0,
                'generator_consumption' => 0,
                'damages' => 0,
                'damage_charges' => $damageCharges, // Show actual damage charges for transparency
                'others' => 0,
                'business_permit' => 0,
                'penalty' => 0,
                'charges' => 0,
                'prev_balance' => $prevBalance,
                'vat' => 0,
                'total_bill' => $prevBalance,
                'is_balance_payment' => true
            ];}        // Normal bill calculation for new billing period or first payment
        Log::info('Generating FULL BILL with charges + prev_balance', [
            'member_id' => $member->member_id,
            'prev_balance' => $prevBalance,
            'present_consumption' => $presentConsumption
        ]);
        
        $excessCharge = $excessMinimumCUM * ($info->excess_minimum_CUM_per_month ?? 15);
        // Exempt certain meter numbers from minimum amount
        $exemptMeters = ['50252', '54603', '5117', '12890', '130'];
        $minimumAmount = in_array((string)($member->meter_no ?? ''), $exemptMeters, true)
            ? 0
            : ($info->minimum_amount_per_month ?? 160);
        $subtotal = $minimumAmount + 
                    $excessCharge +
                    $connectorDamage;        $vatRate = $info->miscellaneous ?? 0;
        $miscellaneous = $vatRate; // Add as whole number, not percentage
        
        // Add damage charges to the bill calculation
        $damageCharges = $member->damage_charges ?? 0;
        $totalBill = $subtotal + $miscellaneous + $generatorConsumption + $electricityConsumption + $damages + $damageCharges + $prevBalance;        return [
            'excess_minimum_CUM' => $excessMinimumCUM,
            'excess_charge' => $excessCharge,
            'minimum_amount' => $minimumAmount,
            'electricity_consumption' => $electricityConsumption,
            'connector_damage' => $connectorDamage,
            'generator_consumption' => $generatorConsumption,
            'damages' => $damages,
            'damage_charges' => $damageCharges,
            'others' => $others,
            'business_permit' => $businessPermit,
            'penalty' => $penalty,            'charges' => $charges,
            'prev_balance' => $prevBalance,
            'vat' => $miscellaneous,
            'total_bill' => $totalBill,
            'is_balance_payment' => false
        ];
    }    /**
     * Check if this is a balance payment only scenario
     * 
     * BUSINESS RULES:
     * 1. If no consumption exists → Check balance (balance > 0 = balance only)
     * 2. If member is UNPAID (is_paid = 0) → Show FULL bill (new reading)
     * 3. If member is PAID (is_paid = 1) with balance > 0:
     *    - If reading_date > last payment_date → NEW reading, show FULL bill
     *    - If reading_date <= last payment_date → NO NEW reading, show BALANCE ONLY
     *    - This allows MULTIPLE PAYMENTS PER MONTH for balance, but only balance amount
     * 4. If member is PAID (is_paid = 1) with balance = 0 → Show full bill
     */
    private function hasCurrentPeriodPayment($memberId, $consumption)
    {
        $member = DB::table('members')->where('member_id', $memberId)->first();
        
        // If no consumption exists at all, check if member has balance
        if (!$consumption) {
            $remainingBalance = $member->prev_balance ?? 0;
            if ($remainingBalance > 0) {
                Log::info('✓ No consumption record, but has balance - balance payment only', [
                    'member_id' => $memberId,
                    'prev_balance' => $remainingBalance
                ]);
                return true; // Balance payment only
            }
            return false; // No consumption, no balance - generate new bill
        }
        
        // If member is marked as UNPAID (is_paid = 0), always show full bill
        // This happens when there's a new reading
        if ($member->is_paid == 0) {
            Log::info('✓ Member is UNPAID - generating FULL bill with prev_balance', [
                'member_id' => $memberId,
                'is_paid' => $member->is_paid,
                'prev_balance' => $member->prev_balance ?? 0
            ]);
            return false; // Generate full bill
        }
          // If member is marked as PAID with a remaining balance,
        // they should only pay the remaining balance (regardless of when payment was made)
        $remainingBalance = $member->prev_balance ?? 0;
        $hasRemainingBalance = $remainingBalance > 0;
        
        if ($member->is_paid == 1 && $hasRemainingBalance) {
            // Get the last payment date for this member
            $lastPayment = DB::table('payments')
                ->where('member_id', $memberId)
                ->orderBy('payment_date', 'desc')
                ->first();
            
            if ($lastPayment) {
                $readingDate = $consumption->reading_date;
                $paymentDate = $lastPayment->payment_date;
                
                // Check if there's a NEW reading AFTER the last payment
                // Only show full bill if there's actually a new reading
                if ($readingDate > $paymentDate) {
                    Log::info('✓ NEW reading after payment - generating FULL bill', [
                        'member_id' => $memberId,
                        'reading_date' => $readingDate,
                        'last_payment_date' => $paymentDate,
                        'is_new_reading' => true
                    ]);
                    return false; // NEW reading - show full bill
                }
                  // For all other cases (same day, same reading, old reading):
                // Show BALANCE ONLY if member has remaining balance
                // This allows MULTIPLE PAYMENTS PER MONTH for balance amounts
                Log::info('✓ Member paid partially, no new reading - balance payment only (multiple payments allowed)', [
                    'member_id' => $memberId,
                    'reading_date' => $readingDate,
                    'last_payment_date' => $paymentDate,
                    'prev_balance' => $remainingBalance,
                    'is_balance_payment' => true,
                    'allows_multiple_payments' => true
                ]);
                return true; // Show balance payment only
            }
            
            // No payment history but has balance - show balance only
            Log::info('✓ No payment history, but has balance - balance payment only', [
                'member_id' => $memberId,
                'prev_balance' => $remainingBalance
            ]);
            return true;
        }
        
        // Member is paid with no balance - show full bill for next period
        Log::info('✓ Member is PAID with no balance - generating FULL bill', [
            'member_id' => $memberId,
            'is_paid' => $member->is_paid,
            'prev_balance' => $remainingBalance
        ]);
        return false;
    }
    
    public function getByAccountNumber($accountNo)
    {
        try {
            $member = DB::table('members as m')
                ->leftJoin('puroks as p', 'm.purok_id', '=', 'p.purok_id')
                ->leftJoin('ts_numbers as t', 'm.ts_Id', '=', 't.ts_Id')
                ->select(
                    'm.*',
                    'p.purok',
                    't.ts_no'
                )
                ->where('m.account_no', $accountNo)
                ->first();

            if (!$member) {
                return response()->json([
                    'message' => 'Member not found with this account number'
                ], 404);
            }

            // Format response to match expected structure
            return response()->json([
                ...(array)$member,
                'purok' => $member->purok ? ['purok' => $member->purok] : null
            ]);        } catch (\Exception $e) {
            \Log::error('Error finding member by account: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to find member'
            ], 500);
        }
    }

    /**
     * Update member's damage charges
     */
    public function updateDamageCharges(Request $request, $id)
    {
        try {
            $request->validate([
                'damage_charges' => 'required|numeric|min:0'
            ]);

            $member = Member::findOrFail($id);
            $member->damage_charges = $request->damage_charges;
            $member->save();

            return response()->json([
                'message' => 'Damage charges updated successfully',
                'damage_charges' => $member->damage_charges
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating damage charges: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update damage charges'
            ], 500);
        }
    }

    /**
     * Reset water consumption for a member (manual defective meter reset)
     */
    public function resetWaterConsumption(Request $request, $id)
    {
        try {
            $member = Member::findOrFail($id);
            
            // Get the latest water consumption record
            $waterConsumption = DB::table('water_consumptions')
                ->where('member_Id', $id)
                ->orderBy('reading_date', 'desc')
                ->first();
                
            if (!$waterConsumption) {
                return response()->json([
                    'message' => 'No water consumption record found for this member'
                ], 404);
            }
            
            Log::info('Manual water consumption reset initiated', [
                'member_id' => $id,
                'account_no' => $member->account_no,
                'initiated_by' => auth()->id(),
                'reason' => 'Manual reset by president',
                'before_reset' => [
                    'prev_CUM_consumption' => $waterConsumption->prev_CUM_consumption,
                    'present_CUM_consumption' => $waterConsumption->present_CUM_consumption,
                    'prev_meter_reading' => $waterConsumption->prev_meter_reading,
                    'present_meter_reading' => $waterConsumption->present_meter_reading
                ]
            ]);
            
            // Reset all consumption and meter readings to 0
            $updateResult = DB::table('water_consumptions')
                ->where('member_Id', $id)
                ->where('reading_date', $waterConsumption->reading_date)
                ->update([
                    'prev_CUM_consumption' => 0,
                    'present_CUM_consumption' => 0,
                    'prev_meter_reading' => 0,
                    'present_meter_reading' => 0
                ]);
            
            Log::info('Water consumption reset completed', [
                'member_id' => $id,
                'account_no' => $member->account_no,
                'rows_affected' => $updateResult
            ]);
            
            return response()->json([
                'message' => 'Water consumption reset successfully',
                'member_id' => $id,
                'account_no' => $member->account_no,
                'reset_date' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('Error resetting water consumption: ' . $e->getMessage(), [
                'member_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to reset water consumption'
            ], 500);
        }
    }
}