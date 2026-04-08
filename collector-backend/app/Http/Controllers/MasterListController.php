<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;

// class MasterListController extends Controller
// {
//     public function index(Request $request)
//     {
//         // Get important_information for calculations
//         $info = DB::table('important_information')->first();
        
//         $query = DB::table('members')
//             ->leftJoin('water_consumptions', function($join) {
//                 $join->on('members.member_id', '=', 'water_consumptions.member_Id')
//                      ->whereRaw('water_consumptions.id = (SELECT MAX(id) FROM water_consumptions WHERE member_Id = members.member_id)');
//             })
//             ->select(
//                 'members.*',
//                 'water_consumptions.present_CUM_consumption',
//                 'water_consumptions.prev_CUM_consumption',
//                 'water_consumptions.reading_date as billing_date'
//             );

//         // Search functionality
//         if ($request->has('search') && $request->search) {
//             $search = $request->search;
//             $query->where(function($q) use ($search) {
//                 $q->where('members.account_no', 'LIKE', "%{$search}%")
//                   ->orWhere('members.fname', 'LIKE', "%{$search}%")
//                   ->orWhere('members.lname', 'LIKE', "%{$search}%")
//                   ->orWhere('members.meter_no', 'LIKE', "%{$search}%");
//             });
//         }

//         $members = $query->get();

//         // Calculate billing details for each member
//         $members = $members->map(function($member) use ($info) {
//             // Calculate CUM consumption
//             $cumConsumption = $member->present_CUM_consumption ?? 0;
            
//             // Calculate excess CUM (anything over free CUM)
//             $freeCUM = $info->free_CUM_per_month ?? 5;
//             $excessCUM = max(0, $cumConsumption - $freeCUM);
            
//             // Calculate charges
//             $minimumAmount = $info->minimum_amount_per_month ?? 160;
//             $excessCharge = $excessCUM * ($info->excess_minimum_CUM_per_month ?? 15);
//             $lossDamage = $info->lossdamage_and_other_charges ?? 0;
//             $aselco = $info->electricity_consumption ?? 0;
//             $diesel = $info->generator_consumption ?? 0;
//             $other = 0;
            
//             // Calculate subtotal before VAT
//             $subtotal = $minimumAmount + $excessCharge + $lossDamage;
            
//             // Calculate VAT
//             $vatPercentage = $info->VAT;
//             $vatAmount = ($subtotal * $vatPercentage) / 100;
            
//             // Calculate total with VAT
//             $total = $subtotal + $vatAmount + $aselco + $diesel + $other;
            
//             // Add calculated fields
//             $member->cum_consumption = $cumConsumption;
//             $member->minimum_amount = $minimumAmount;
//             $member->excess_cum = $excessCharge;
//             $member->loss_damage = $lossDamage;
//             $member->aselco = $aselco;
//             $member->diesel = $diesel;
//             $member->other = $other;
//             $member->vat_percentage = $vatPercentage;
//             $member->vat_amount = $vatAmount;
//             $member->total = $total;
            
//             return $member;
//         });

//         return response()->json($members);
//     }
// }


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterListController extends Controller
{
    public function index(Request $request)
    {
        // Get important_information for calculations
        $info = DB::table('important_information')->first();
          $query = DB::table('members')
            ->leftJoin('water_consumptions', function($join) {
                $join->on('members.member_id', '=', 'water_consumptions.member_Id')
                     ->whereRaw('water_consumptions.id = (SELECT MAX(id) FROM water_consumptions WHERE member_Id = members.member_id)');
            })
            ->leftJoin('ts_numbers', 'members.ts_Id', '=', 'ts_numbers.ts_Id')            ->select(
                'members.*',
                'ts_numbers.ts_no',
                'ts_numbers.landmark',
                'water_consumptions.present_CUM_consumption',
                'water_consumptions.prev_CUM_consumption',
                'water_consumptions.present_meter_reading',
                'water_consumptions.prev_meter_reading',
                'water_consumptions.reading_date as billing_date'
            );        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('members.account_no', 'LIKE', "%{$search}%")
                  ->orWhere('members.fname', 'LIKE', "%{$search}%")
                  ->orWhere('members.lname', 'LIKE', "%{$search}%")
                  ->orWhere('members.meter_no', 'LIKE', "%{$search}%");
            });
        }

        $members = $query
            ->orderBy('members.ts_Id')
            ->orderBy('members.account_no')
            ->get();

        // Calculate billing details for each member using the same logic as MemberController
        $members = $members->map(function($member) use ($info) {
            // Bill calculation logic from MemberController
            $freeCumPerMonth = $info->free_CUM_per_month ?? 5;
            $presentConsumption = $member->present_CUM_consumption ?? 0;
              $excessMinimumCUM = max(0, $presentConsumption - $freeCumPerMonth);
              $electricityConsumption = $info->electricity_consumption ?? 0;
            $connectorDamage = $info->connector_damage_with_unknown_person ?? 0;
            $generatorConsumption = $info->generator_consumption ?? 0;
            $damages = $info->lossdamage_and_other_charges ?? 0;
            $miscellaneous = $info->miscellaneous ?? 0;
            $damageCharges = $member->damage_charges ?? 0;
            $others = 0;
            $businessPermit = 0;
            $penalty = 0;
            $charges = 0;
            $prevBalance = $member->prev_balance ?? 0;

            $excessCharge = $excessMinimumCUM * ($info->excess_minimum_CUM_per_month ?? 15);
            $minimumAmount = $info->minimum_amount_per_month ?? 160;

            $subtotal = $minimumAmount +
                        $excessCharge +
                        $connectorDamage +
                        $prevBalance;            $vatRate = $info->VAT ?? 0;
            $vat = $subtotal * ($vatRate / 100);
            $totalBill = $subtotal + $vat + $generatorConsumption + $electricityConsumption + $damages + $prevBalance + $damageCharges;            // Add calculated fields to member object
            $member->cum_consumption = $presentConsumption;
            $member->excess_minimum_CUM = $excessMinimumCUM;
            $member->minimum_amount = $minimumAmount;
            $member->excess_cum = $excessCharge;
            $member->electricity_consumption = $electricityConsumption;
            $member->connector_damage = $connectorDamage;
            $member->generator_consumption = $generatorConsumption;
            $member->loss_damage = $damages;
            $member->miscellaneous = $miscellaneous;
            $member->damage_charges = $damageCharges;
            $member->aselco = $electricityConsumption;
            $member->diesel = $generatorConsumption;
            $member->others = $others;
            $member->business_permit = $businessPermit;
            $member->penalty = $penalty;
            $member->charges = $charges;
            $member->prev_balance = $prevBalance;
            $member->vat_percentage = $vatRate;
            $member->vat_amount = $vat;
            $member->total = $totalBill;
            
            return $member;
        });

        return response()->json($members);
    }


}