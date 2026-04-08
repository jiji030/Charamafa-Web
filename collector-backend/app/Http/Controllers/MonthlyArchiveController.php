<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonthlyArchiveController extends Controller
{
    public function generate(Request $request)
    {
        $year  = (int)($request->input('year') ?? now()->year);
        $month = (int)($request->input('month') ?? now()->month);

        DB::beginTransaction();
        try {
            $date  = Carbon::create($year, $month, 1);
            $label = $date->format('F Y'); // e.g. "January 2026"

            // Billing period runs from 10th of current month to 9th of next month
            // Snapshot date is the 9th at end of billing period
            $billingEndDate = Carbon::create($year, $month, 9)->endOfDay()->toDateString();

            // Create or update billing period (only if it doesn't exist)
            $period = DB::table('billing_periods')
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if (!$period) {
                // Only create once - on the 10th when billing period starts
                DB::table('billing_periods')->insert([
                    'year'         => $year,
                    'month'        => $month,
                    'label'        => $label,
                    'billing_date' => $billingEndDate,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                $period = DB::table('billing_periods')
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();
            }

            if (!$period) {
                throw new \RuntimeException('Failed to create billing period record.');
            }

            // Check if snapshot already exists for this period
            $existingCount = DB::table('monthly_master_lists')
                ->where('billing_period_id', $period->id)
                ->count();

            // Only save snapshot if it doesn't exist yet (first time on 10th)
            // After that, update the same snapshot with latest values until 9th
            if ($existingCount > 0) {
                // Update existing snapshot with latest values
                DB::table('monthly_master_lists')
                    ->where('billing_period_id', $period->id)
                    ->delete();
            }            // Get important_information for calculations (same as MasterListController)
            $info = DB::table('important_information')->first();            // Get members with latest water consumption (same logic as MasterListController)
            $rows = DB::table('members')
                ->leftJoin('water_consumptions', function($join) {
                    $join->on('members.member_id', '=', 'water_consumptions.member_Id')
                         ->whereRaw('water_consumptions.id = (SELECT MAX(id) FROM water_consumptions WHERE member_Id = members.member_id)');
                })
                ->leftJoin('ts_numbers', 'members.ts_Id', '=', 'ts_numbers.ts_Id')                ->select(
                    'members.member_id',
                    'members.account_no',
                    'members.meter_no',
                    'members.fname',
                    'members.lname',
                    'members.mname',
                    'members.ts_Id',
                    'members.damage_charges',
                    'members.prev_balance',
                    'members.connection_status',
                    'ts_numbers.ts_no',
                    'ts_numbers.landmark',
                    'water_consumptions.present_CUM_consumption',
                    'water_consumptions.prev_CUM_consumption',
                    'water_consumptions.present_meter_reading',
                    'water_consumptions.prev_meter_reading'
                )
                ->get();

            // Calculate billing details for each member (same logic as MasterListController)
            foreach ($rows as $m) {
                // Bill calculation logic from MasterListController
                $freeCumPerMonth = $info->free_CUM_per_month ?? 5;
                $presentConsumption = $m->present_CUM_consumption ?? 0;
                $excessMinimumCUM = max(0, $presentConsumption - $freeCumPerMonth);
                $electricityConsumption = $info->electricity_consumption ?? 0;
                $connectorDamage = $info->connector_damage_with_unknown_person ?? 0;
                $generatorConsumption = $info->generator_consumption ?? 0;
                $damages = $info->lossdamage_and_other_charges ?? 0;
                $miscellaneous = $info->miscellaneous ?? 0;
                $damageCharges = $m->damage_charges ?? 0;
                $others = 0;
                $prevBalance = $m->prev_balance ?? 0;

                $excessCharge = $excessMinimumCUM * ($info->excess_minimum_CUM_per_month ?? 15);
                $minimumAmount = $info->minimum_amount_per_month ?? 160;

                $subtotal = $minimumAmount +
                            $excessCharge +
                            $connectorDamage +
                            $prevBalance;
                
                $vatRate = $info->VAT ?? 0;
                $vat = $subtotal * ($vatRate / 100);
                $totalBill = $subtotal + $vat + $generatorConsumption + $electricityConsumption + $damages + $prevBalance + $damageCharges;                DB::table('monthly_master_lists')->insert([
                    'billing_period_id' => $period->id,
                    'member_id'         => $m->member_id,
                    'ts_id'             => $m->ts_Id,
                    'ts_no'             => $m->ts_no,
                    'landmark'          => $m->landmark,
                    'account_no'        => $m->account_no,
                    'meter_no'          => $m->meter_no,
                    'name'              => trim("{$m->lname}, {$m->fname} " . ($m->mname ? substr($m->mname, 0, 1) . '.' : '')),
                    'prev_CUM_consumption' => $m->prev_CUM_consumption ?? 0,
                    'present_CUM_consumption' => $presentConsumption,
                    'cum_consumption'   => $presentConsumption,
                    'prev_meter_reading' => $m->prev_meter_reading,
                    'present_meter_reading' => $m->present_meter_reading,
                    'minimum_amount'    => $minimumAmount,
                    'excess_cum'        => $excessCharge,
                    'damage_charges'    => $damageCharges,
                    'miscellaneous'     => $miscellaneous,
                    'aselco'            => $electricityConsumption,
                    'diesel'            => $generatorConsumption,
                    'others'            => $others,
                    'total_bill'        => $totalBill,
                    'billing_date'      => $date->endOfMonth()->toDateString(),
                    'connection_status' => $m->connection_status ?? 1,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message'           => 'Monthly master list snapshot saved.',
                'billing_period_id' => $period->id,
                'label'             => $period->label,
            ]);        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Billing period generation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
