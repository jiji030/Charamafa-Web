<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReconnectionController extends Controller
{    public function show($id)
    {
        $member = Member::with(['purok', 'tsNumber'])->findOrFail($id);
        
        // Default reconnection fee
        $reconnectionFee = 100;

        // Get disconnection reason if exists
        $disconnectionLog = null;
        try {
            $disconnectionLog = DB::table('disconnection_logs')
                ->where('member_id', $id)
                ->latest('disconnected_at')
                ->first();
        } catch (\Exception $e) {
            // Table doesn't exist yet
        }

        // Get shared meter information
        $sharedMembers = $member->sharedMeterMembers();
        $sharedMeterBalance = $member->getSharedMeterBalance();

        return response()->json([
            'member' => $member,
            'reconnection_fee' => $reconnectionFee,
            'prev_balance' => $sharedMeterBalance,
            'disconnection_reason' => $disconnectionLog ? $disconnectionLog->reason : null,
            'disconnected_at' => $disconnectionLog ? $disconnectionLog->disconnected_at : null,
            'shared_meter_info' => [
                'meter_no' => $member->meter_no,
                'total_members' => $sharedMembers->count(),
                'members' => $sharedMembers->map(function($m) {
                    return [
                        'member_id' => $m->member_id,
                        'account_no' => $m->account_no,
                        'full_name' => $m->full_name,
                        'connection_status' => $m->connection_status
                    ];
                })
            ]
        ]);
    }

    public function processReconnection(Request $request, $id)
    {
        $request->validate([
            'cash' => 'required|numeric|min:0',
            'reconnection_fee' => 'required|numeric|min:0',
            'pay_balance' => 'required|boolean'
        ]);        $member = Member::findOrFail($id);
        
        // Get shared meter balance (should be consistent across shared meter members)
        $sharedMeterBalance = $member->getSharedMeterBalance();
        
        // CRITICAL: If shared meter has balance, they MUST pay it to reconnect
        if ($sharedMeterBalance > 0 && !$request->pay_balance) {
            return response()->json([
                'message' => 'Cannot reconnect without paying previous balance',
                'prev_balance' => $sharedMeterBalance,
                'must_pay_balance' => true,
                'shared_meter_info' => [
                    'meter_no' => $member->meter_no,
                    'members_affected' => $member->sharedMeterMembers()->count()
                ]
            ], 400);
        }        DB::beginTransaction();
        try {
            $reconnectionFee = $request->reconnection_fee;
            $prevBalancePaid = 0;
            $remainingBalance = $sharedMeterBalance;

            // Calculate amounts
            if ($request->pay_balance && $sharedMeterBalance > 0) {
                $prevBalancePaid = $sharedMeterBalance;
                $remainingBalance = 0;
            }

            $totalAmount = $reconnectionFee + $prevBalancePaid;
            $change = $request->cash - $totalAmount;

            // Validate sufficient payment
            if ($change < 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Insufficient payment amount',
                    'required' => $totalAmount,
                    'received' => $request->cash,
                    'shortage' => abs($change)
                ], 400);
            }

            // Update shared meter connection status - RECONNECTED
            $member->updateSharedMeterConnectionStatus(1, now()->toDateString());
            
            // Update shared meter balance if paid
            if ($request->pay_balance && $sharedMeterBalance > 0) {
                $member->updateSharedMeterPaymentStatus(true, 0);
            }

            // Log shared meter reconnection
            $sharedMembers = $member->sharedMeterMembers();
            \Log::info('Shared meter reconnection processed', [
                'reconnecting_member' => $member->account_no,
                'meter_no' => $member->meter_no,
                'affected_members' => $sharedMembers->pluck('account_no')->toArray(),
                'reconnection_fee' => $reconnectionFee,
                'previous_balance_paid' => $prevBalancePaid
            ]);            // Record reconnection payment in payments table with new structure
            // Get next available INVOICE number
            $orNumber = null;
            $canPrintReceipt = false;
            $nextOr = DB::table('or_numbers')
                ->where('is_used', 0)
                ->orderBy('or_number', 'asc')
                ->first();
            
            if (!$nextOr) {
                // No INVOICE numbers available, but allow reconnection processing
                \Log::warning('No INVOICE numbers available - reconnection will be processed without INVOICE number', [
                    'member_id' => $id,
                    'amount' => $totalAmount
                ]);
                $orNumber = 'NO-INV-RECON-' . $id . '-' . time(); // Generate temporary INVOICE reference
                $canPrintReceipt = false;
            } else {
                $orNumber = $nextOr->or_number;
                $canPrintReceipt = true;
                
                // Mark INVOICE number as used
                DB::table('or_numbers')
                    ->where('or_id', $nextOr->or_id)
                    ->update([
                        'is_used' => 1,
                        'used_at' => now(),
                        'used_by' => auth()->id()
                    ]);
            }
            
            // Get water consumption data for billing info
            $waterConsumption = DB::table('water_consumptions')
                ->where('member_Id', $id)
                ->orderBy('reading_date', 'desc')
                ->first();
              $cumUsage = $waterConsumption->present_CUM_consumption ?? 0;
            $meterReading = $waterConsumption->present_meter_reading ?? 0;
            $reference = 'RECON-' . $member->account_no . '-' . date('Ym');
            
            $payment = Payment::create([
                'member_id' => $id,
                'billing_date' => now()->format('Y-m-d'),
                'reference' => $reference,
                'meter_reading' => $meterReading,
                'cum_usage' => $cumUsage,
                'bill_amount' => $totalAmount,
                'balance' => $member->prev_balance,
                'amount_paid' => $totalAmount,
                'payment_date' => now(),
                'or_number' => $orNumber,
                'payment_method' => 'Cash',
                'collected_by' => auth()->id() ?? 1
            ]);DB::commit();

            // Get updated shared members info
            $sharedMembers = $member->fresh()->sharedMeterMembers();

            return response()->json([
                'message' => 'Reconnection processed successfully',
                'member' => $member->fresh(),
                'shared_meter_info' => [
                    'meter_no' => $member->meter_no,
                    'total_members_affected' => $sharedMembers->count(),
                    'affected_members' => $sharedMembers->map(function($m) {
                        return [
                            'member_id' => $m->member_id,
                            'account_no' => $m->account_no,
                            'full_name' => $m->full_name,
                            'connection_status' => $m->connection_status,
                            'prev_balance' => $m->prev_balance
                        ];
                    })
                ],
                'payment' => $payment,
                'cash' => $request->cash,
                'reconnection_fee' => $reconnectionFee,
                'prev_balance_paid' => $prevBalancePaid,
                'remaining_balance' => $remainingBalance,                'total_amount' => $totalAmount,
                'change' => $change,
                'or_number' => $orNumber,
                'can_print_receipt' => $canPrintReceipt,                'or_warning' => !$canPrintReceipt ? 'Reconnection processed without INVOICE number. Receipt cannot be printed.' : null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Reconnection failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Reconnection failed', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function payBalanceOnly(Request $request, $id)
    {
        $request->validate([
            'cash' => 'required|numeric|min:0'
        ]);        $member = Member::findOrFail($id);
        
        // Get shared meter balance
        $sharedMeterBalance = $member->getSharedMeterBalance();
        
        if ($sharedMeterBalance <= 0) {
            return response()->json([
                'message' => 'No balance to pay',
                'shared_meter_info' => [
                    'meter_no' => $member->meter_no,
                    'members_affected' => $member->sharedMeterMembers()->count()
                ]
            ], 400);
        }        DB::beginTransaction();
        try {
            $totalAmount = $sharedMeterBalance;
            $change = $request->cash - $totalAmount;

            // Validate sufficient payment
            if ($change < 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Insufficient payment amount',
                    'required' => $totalAmount,
                    'received' => $request->cash,
                    'shortage' => abs($change)
                ], 400);
            }

            // Update shared meter balance to 0 (keep disconnected status)
            $member->updateSharedMeterPaymentStatus(false, 0); // false = don't mark as paid, just clear balance

            // Log shared meter balance payment
            $sharedMembers = $member->sharedMeterMembers();
            \Log::info('Shared meter balance payment processed', [
                'paying_member' => $member->account_no,
                'meter_no' => $member->meter_no,
                'affected_members' => $sharedMembers->pluck('account_no')->toArray(),
                'amount_paid' => $totalAmount
            ]);            $orNumber = null;
            $canPrintReceipt = false;
            $nextOr = DB::table('or_numbers')
                ->where('is_used', 0)
                ->orderBy('or_number', 'asc')
                ->first();
            
            if (!$nextOr) {
                // No INVOICE numbers available, but allow balance payment processing
                \Log::warning('No INVOICE numbers available - balance payment will be processed without INVOICE number', [
                    'member_id' => $id,
                    'amount' => $totalAmount
                ]);
                $orNumber = 'NO-INV-BAL-' . $id . '-' . time(); // Generate temporary INVOICE reference
                $canPrintReceipt = false;
            } else {
                $orNumber = $nextOr->or_number;
                $canPrintReceipt = true;
                
                // Mark INVOICE number as used
                DB::table('or_numbers')
                    ->where('or_id', $nextOr->or_id)
                    ->update([
                        'is_used' => 1,
                        'used_at' => now(),
                        'used_by' => auth()->id()
                    ]);
            }
            
            // Get water consumption data for billing info  
            $waterConsumption = DB::table('water_consumptions')
                ->where('member_Id', $id)
                ->orderBy('reading_date', 'desc')
                ->first();
            
            $cumUsage = $waterConsumption->present_CUM_consumption ?? 0;
            $meterReading = $waterConsumption->present_meter_reading ?? 0;            $reference = 'BAL-' . $member->account_no . '-' . date('Ym');
            
            // Record balance payment with new structure
            $payment = Payment::create([
                'member_id' => $id,
                'billing_date' => now()->format('Y-m-d'),
                'reference' => $reference,
                'meter_reading' => $meterReading,
                'cum_usage' => $cumUsage,
                'bill_amount' => $totalAmount,
                'balance' => $member->prev_balance,
                'amount_paid' => $totalAmount,
                'payment_date' => now(),
                'or_number' => $orNumber,
                'payment_method' => 'Cash',
                'collected_by' => auth()->id() ?? 1
            ]);DB::commit();

            // Get updated shared members info
            $sharedMembers = $member->fresh()->sharedMeterMembers();

            return response()->json([
                'message' => 'Balance payment processed successfully',
                'member' => $member->fresh(),
                'shared_meter_info' => [
                    'meter_no' => $member->meter_no,
                    'total_members_affected' => $sharedMembers->count(),
                    'affected_members' => $sharedMembers->map(function($m) {
                        return [
                            'member_id' => $m->member_id,
                            'account_no' => $m->account_no,
                            'full_name' => $m->full_name,
                            'connection_status' => $m->connection_status,
                            'prev_balance' => $m->prev_balance
                        ];
                    })
                ],
                'payment' => $payment,
                'amount_paid' => $totalAmount,                'cash' => $request->cash,
                'change' => $change,
                'remaining_balance' => 0,
                'or_number' => $orNumber,
                'can_print_receipt' => $canPrintReceipt,                'or_warning' => !$canPrintReceipt ? 'Balance payment processed without INVOICE number. Receipt cannot be printed.' : null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Balance payment failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Balance payment failed', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}