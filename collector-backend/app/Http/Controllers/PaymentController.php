<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{    public function processPayment(Request $request, $id)
    {
        \Log::info('Starting payment process', ['member_id' => $id, 'request' => $request->all()]);
        
        try {
            $validated = $request->validate([
                'cash' => 'required|numeric|min:0.01',
                'total_bill' => 'required|numeric|min:0',
                'is_defective_meter' => 'boolean',
                'is_partial_payment' => 'boolean'
            ]);
        } catch (\Exception $e) {
            \Log::error('Validation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Invalid input data: ' . $e->getMessage()], 422);
        }        try {
            $member = Member::findOrFail($id);
        } catch (\Exception $e) {
            \Log::error('Member not found', ['member_id' => $id]);
            return response()->json(['message' => 'Member not found'], 404);
        }
          // Check if already paid completely (no remaining balance)
        // Allow multiple payments when there's still a balance to pay
        if ($member->hasSharedMeterPaid() && $member->prev_balance <= 0) {
            \Log::info('Payment rejected - shared meter fully paid with no balance', [
                'member_id' => $id, 
                'meter_no' => $member->meter_no,
                'prev_balance' => $member->prev_balance
            ]);
            
            // Get the shared meter members for detailed response
            $sharedMembers = $member->sharedMeterMembers();
            $paidMember = $sharedMembers->where('is_paid', 1)->first();
            
            return response()->json([
                'message' => 'This meter has already been paid in full for the current billing cycle.',
                'paid_by' => $paidMember ? [
                    'account_no' => $paidMember->account_no,
                    'full_name' => $paidMember->full_name
                ] : null,
                'shared_meter_no' => $member->meter_no,
                'shared_members' => $sharedMembers->map(function($m) {
                    return [
                        'account_no' => $m->account_no,
                        'full_name' => $m->full_name,
                        'is_paid' => $m->is_paid,
                        'prev_balance' => $m->prev_balance
                    ];
                })
            ], 400);
        }
        
        // Log when multiple payments are allowed
        if ($member->hasSharedMeterPaid() && $member->prev_balance > 0) {
            \Log::info('Multiple payment allowed - member has remaining balance', [
                'member_id' => $id,
                'meter_no' => $member->meter_no,
                'prev_balance' => $member->prev_balance,
                'is_defective_meter' => $request->is_defective_meter ?? false
            ]);
        }DB::beginTransaction();
        try {
            // Calculate amounts
            $cash = $request->cash;
            $totalBill = $request->total_bill;
            $change = max(0, $cash - $totalBill);
            $amountPaid = min($cash, $totalBill);
            $remainingBalance = max(0, $totalBill - $amountPaid);            // Get next available INVOICE number
            $orNumber = null;
            $canPrintReceipt = false;
            $nextOr = DB::table('or_numbers')
                ->where('is_used', 0)
                ->orderBy('or_number', 'asc')
                ->first();
            
            if (!$nextOr) {
                // No INVOICE numbers available, but allow payment processing
                \Log::warning('No INVOICE numbers available - payment will be processed without INVOICE number', [
                    'member_id' => $id,
                    'amount' => $amountPaid
                ]);
                $orNumber = 'NO-INV-' . $id . '-' . time(); // Generate temporary INVOICE reference
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
            }// Handle defective meter payments - reset consumption only when explicitly marked
            if ($request->is_defective_meter) {
                \Log::info('Processing defective meter payment - resetting consumption', [
                    'member_id' => $id,
                    'account_no' => $member->account_no
                ]);
                
                try {
                    // Reset water consumption for defective meter
                    $waterConsumption = DB::table('water_consumptions')
                        ->where('member_Id', $id)
                        ->orderBy('reading_date', 'desc')
                        ->first();
                        
                    if ($waterConsumption) {
                        \Log::info('Found consumption record to reset', [
                            'member_id' => $id,
                            'reading_date' => $waterConsumption->reading_date,
                            'current_prev_cum' => $waterConsumption->prev_CUM_consumption ?? 'null',
                            'current_present_cum' => $waterConsumption->present_CUM_consumption ?? 'null',
                            'current_prev_meter' => $waterConsumption->prev_meter_reading ?? 'null',
                            'current_present_meter' => $waterConsumption->present_meter_reading ?? 'null'
                        ]);
                          // Reset all consumption and meter readings to 0 for defective meter
                        $updateData = [
                            'prev_CUM_consumption' => 0,
                            'present_CUM_consumption' => 0,
                            'prev_meter_reading' => 0,
                            'present_meter_reading' => 0
                        ];
                        
                        // Update the current consumption record to reset all values
                        $updateResult = DB::table('water_consumptions')
                            ->where('member_Id', $id)
                            ->where('reading_date', $waterConsumption->reading_date)
                            ->update($updateData);
                        
                        \Log::info('Water consumption completely reset for defective meter', [
                            'member_id' => $id,
                            'reading_date' => $waterConsumption->reading_date,
                            'rows_affected' => $updateResult,
                            'reset_data' => $updateData
                        ]);
                    } else {
                        \Log::warning('No water consumption record found for defective meter reset', [
                            'member_id' => $id
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to reset consumption for defective meter', [
                        'member_id' => $id,
                        'error' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString()
                    ]);
                    // Continue with payment processing even if consumption reset fails
                }
            }
            
            if ($request->is_defective_meter) {
                \Log::info('Defective meter payment completed with consumption reset', [
                    'member_id' => $id,
                    'account_no' => $member->account_no
                ]);
            } else {
                \Log::info('Regular payment - consumption data maintained', [
                    'member_id' => $id,
                    'is_partial' => $amountPaid < $totalBill
                ]);
            }

            // Get water consumption data for this member
            $waterConsumption = DB::table('water_consumptions')
                ->where('member_Id', $id)
                ->orderBy('reading_date', 'desc')
                ->first();
            
            // Calculate CUM usage
            $cumUsage = 0;
            $meterReading = 0;
            if ($waterConsumption) {
                $cumUsage = $waterConsumption->present_CUM_consumption ?? 0;
                $meterReading = $waterConsumption->present_meter_reading ?? 0;
            }
            
            // Generate billing reference
            $reference = 'BILL-' . $member->account_no . '-' . date('Ym');
            
            \Log::info('Creating payment record', [
                'member_id' => $id,
                'amount' => $amountPaid,
                'or_number' => $orNumber,
                'bill_amount' => $totalBill,
                'meter_reading' => $meterReading,
                'cum_usage' => $cumUsage
            ]);            // Create payment record with new structure
            $payment = new Payment();
            $payment->member_id = $id;
            $payment->billing_date = now()->format('Y-m-d');
            $payment->reference = $reference;
            $payment->meter_reading = $meterReading;
            $payment->cum_usage = $cumUsage;
            $payment->bill_amount = $totalBill;
            $payment->balance = $member->prev_balance ?? 0;
            $payment->amount_paid = $amountPaid;
            $payment->payment_date = now();
            $payment->or_number = $orNumber;
            $payment->payment_method = 'Cash';
            $payment->collected_by = auth()->id();
            $payment->save();            // Update member status for shared meters
            // Always mark as paid when any payment is made (full or partial)
            $member->updateSharedMeterPaymentStatus(true, $remainingBalance);

            // Reset damage charges to 0 after payment is processed
            $member->damage_charges = 0;
            $member->save();

            // Log shared meter payment details
            $sharedMembers = $member->sharedMeterMembers();
            \Log::info('Shared meter payment processed', [
                'paying_member' => $member->account_no,
                'meter_no' => $member->meter_no,
                'affected_members' => $sharedMembers->pluck('account_no')->toArray(),
                'amount_paid' => $amountPaid,
                'remaining_balance' => $remainingBalance
            ]);

            // Update collection record
            $this->updateCollection(now(), $amountPaid, auth()->id());            DB::commit();

            // Get updated shared members info
            $sharedMembers = $member->fresh()->sharedMeterMembers();
              return response()->json([
                'message' => 'Payment processed successfully',
                'member' => $member->fresh(),
                'shared_meter_info' => [
                    'meter_no' => $member->meter_no,
                    'total_members_affected' => $sharedMembers->count(),
                    'affected_members' => $sharedMembers->map(function($m) {
                        return [
                            'member_id' => $m->member_id,
                            'account_no' => $m->account_no,
                            'full_name' => $m->full_name,
                            'is_paid' => $m->is_paid,
                            'prev_balance' => $m->prev_balance
                        ];
                    })                ],                
                    'payment' => [
                    'id' => $payment->payment_id,
                    'billing_date' => $payment->billing_date,
                    'reference' => $payment->reference,
                    'meter_reading' => $payment->meter_reading,
                    'cum_usage' => $payment->cum_usage,
                    'bill_amount' => $payment->bill_amount,
                    'balance' => $payment->balance,
                    'amount_paid' => $amountPaid,
                    'payment_date' => $payment->payment_date,
                    'or_number' => $orNumber
                ],
                'cash' => $cash,
                'total_bill' => $totalBill,
                'change' => $change,
                'remaining_balance' => $remainingBalance,                'can_print_receipt' => $canPrintReceipt,
                'or_warning' => !$canPrintReceipt ? 'Payment processed without INVOICE number. Receipt cannot be printed.' : null
            ]);} catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'member_id' => $id,
                'data' => [
                    'amount_paid' => $amountPaid ?? null,
                    'cash' => $cash ?? null,
                    'total_bill' => $totalBill ?? null
                ]
            ]);
            return response()->json([
                'message' => 'Payment failed: ' . $e->getMessage(),
                'debug_message' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    private function generatePaymentNotes($isPartial, $isDefective, $cash, $change, $remaining)
    {
        $notes = [];
        
        if ($isPartial) {
            $notes[] = "Partial payment";
        } else {
            $notes[] = "Full payment";
        }
        
        if ($isDefective) {
            $notes[] = "Defective meter";
        }
        
        $notes[] = "Cash: ₱" . number_format($cash, 2);
        $notes[] = "Change: ₱" . number_format($change, 2);
        
        if ($remaining > 0) {
            $notes[] = "Remaining: ₱" . number_format($remaining, 2);
        }
        
        return implode(" | ", $notes);
    }

    private function updateCollection($paymentDate, $amount, $collectorId)
    {
        try {
            $date = new \DateTime($paymentDate);
            $day = (int) $date->format('d');
            
            // Determine billing month (10th to 9th cycle)
            if ($day >= 10) {
                // Payment on or after 10th belongs to current month
                $billingDate = $date;
            } else {
                // Payment before 10th belongs to previous month
                $billingDate = (clone $date)->modify('-1 month');
            }
            
            $month = $billingDate->format('F'); // e.g., "November"
            $year = (int) $billingDate->format('Y');

            // Check if collection record exists for this month/year
            $existing = DB::table('collections')
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($existing) {
                // Update existing record - increment total
                DB::table('collections')
                    ->where('collection_id', $existing->collection_id)
                    ->update([
                        'total_collection' => DB::raw('total_collection + ' . $amount),
                        'collection_date' => now()
                    ]);
            } else {
                // Create new record
                DB::table('collections')->insert([
                    'month' => $month,
                    'year' => $year,
                    'total_collection' => $amount,
                    'collector_id' => $collectorId,
                    'collection_date' => now(),
                    'created_at' => now()
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error updating collection: ' . $e->getMessage());
            // Don't throw - payment should still succeed even if collection update fails
        }
    }

    public function getLatestPayment($id)
    {
        $member = Member::with(['purok', 'tsNumber', 'membershipFee'])
            ->findOrFail($id);

        $latestPayment = Payment::where('member_id', $id)
            ->orderBy('payment_date', 'desc')
            ->orderBy('payment_id', 'desc')
            ->first();

        if (!$latestPayment) {
            return response()->json([
                'message' => 'No payment found for this member'
            ], 404);
        }

        // Parse notes to get payment details
        $paymentDetails = $this->parsePaymentNotes($latestPayment->notes);

        // Get bill details for the receipt
        $info = \App\Models\ImportantInformation::first();
        $latestConsumption = $member->waterConsumptions()
            ->latest('reading_date')
            ->first();

        return response()->json([
            'payment' => $latestPayment,
            'member' => $member,
            'latest_consumption' => $latestConsumption,
            'info' => $info,
            'payment_details' => $paymentDetails
        ]);
    }

    private function generateReceiptNumber($member)
    {
        $timestamp = now()->format('YmdHis');
        return "OR-{$member->account_no}-{$timestamp}";
    }

    private function parsePaymentNotes($notes)
    {
        if (!$notes) {
            return [
                'is_partial' => false,
                'is_defective_meter' => false,
                'cash' => 0,
                'change' => 0,
                'remaining_balance' => 0
            ];
        }

        $isPartial = strpos($notes, 'Partial payment') !== false;
        $isDefective = strpos($notes, 'Defective meter') !== false;
        
        // Extract amounts using regex
        preg_match('/Cash: ₱([\d,]+\.\d{2})/', $notes, $cashMatch);
        preg_match('/Change: ₱([\d,]+\.\d{2})/', $notes, $changeMatch);
        preg_match('/Remaining: ₱([\d,]+\.\d{2})/', $notes, $remainingMatch);
        
        return [
            'is_partial' => $isPartial,
            'is_defective_meter' => $isDefective,
            'cash' => isset($cashMatch[1]) ? floatval(str_replace(',', '', $cashMatch[1])) : 0,
            'change' => isset($changeMatch[1]) ? floatval(str_replace(',', '', $changeMatch[1])) : 0,
            'remaining_balance' => isset($remainingMatch[1]) ? floatval(str_replace(',', '', $remainingMatch[1])) : 0
        ];
    }    /**
     * Get payment history for a specific member
     */
    public function getMemberPaymentHistory(Request $request, $memberId)
    {
        try {
            // Get member info
            $member = Member::findOrFail($memberId);
            
            // Get payment history with or without pagination
            $paymentsQuery = Payment::where('member_id', $memberId)
                ->orderBy('payment_date', 'desc')
                ->orderBy('billing_date', 'desc');
            
            if ($request->get('all') === 'true') {
                // Return all records for printing
                $payments = $paymentsQuery->get();
            } else {
                // Return paginated results
                $payments = $paymentsQuery->paginate(20);
            }
            
            // Calculate summary statistics
            $totalPaid = Payment::where('member_id', $memberId)->sum('amount_paid');
            $totalBilled = Payment::where('member_id', $memberId)->sum('bill_amount');
            $lastPayment = Payment::where('member_id', $memberId)
                ->latest('payment_date')
                ->first();
            
            return response()->json([
                'member' => [
                    'member_id' => $member->member_id,
                    'account_no' => $member->account_no,
                    'name' => trim("{$member->fname} {$member->mname} {$member->lname} {$member->suffix}"),
                    'current_balance' => $member->prev_balance ?? 0,
                    'connection_status' => $member->connection_status,
                    'is_paid' => $member->is_paid
                ],
                'payments' => $payments,
                'summary' => [
                    'total_paid' => $totalPaid,
                    'total_billed' => $totalBilled,
                    'outstanding_balance' => $member->prev_balance ?? 0,
                    'last_payment_date' => $lastPayment ? $lastPayment->payment_date : null,
                    'last_payment_amount' => $lastPayment ? $lastPayment->amount_paid : 0,
                    'total_transactions' => Payment::where('member_id', $memberId)->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payment history error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load payment history'], 500);
        }
    }
}