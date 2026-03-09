<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportantInformationController extends Controller
{
    /**
     * Get the important information (billing settings)
     */
    public function show()
    {
        try {
            // Get the first (and should be only) record
            $info = DB::table('important_information')->first();
              if (!$info) {
                // Return default values if no record exists
                return response()->json([
                    'id' => null,
                    'minimum_amount_per_month' => 160.0,
                    'excess_minimum_CUM_per_month' => 15.0,
                    'lossdamage_and_other_charges' => 0.0,
                    'electricity_consumption' => 0.0,
                    'generator_consumption' => 0.0,
                    'announcement' => '',
                    'free_CUM_per_month' => 5,
                    'miscellaneous' => 0,
                    'connector_damage_with_unknown_person' => 0.0
                ]);
            }
            
            return response()->json($info);
            
        } catch (\Exception $e) {
            Log::error('Failed to load important information: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load information',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create new important information record
     */    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'minimum_amount_per_month' => 'required|numeric|min:0',
                'excess_minimum_CUM_per_month' => 'required|numeric|min:0',
                'lossdamage_and_other_charges' => 'nullable|numeric|min:0',
                'electricity_consumption' => 'nullable|numeric|min:0',
                'generator_consumption' => 'nullable|numeric|min:0',
                'announcement' => 'nullable|string',
                'free_CUM_per_month' => 'nullable|integer|min:0',
                'miscellaneous' => 'nullable|numeric|min:0',
                'connector_damage_with_unknown_person' => 'nullable|numeric|min:0'
            ]);
            
            // Check if a record already exists
            $existing = DB::table('important_information')->first();
            
            if ($existing) {
                return response()->json([
                    'message' => 'Record already exists. Please use update instead.',
                    'id' => $existing->id
                ], 400);
            }
            
            // Set defaults for nullable fields
            $validated['lossdamage_and_other_charges'] = $validated['lossdamage_and_other_charges'] ?? 0;
            $validated['electricity_consumption'] = $validated['electricity_consumption'] ?? 0;
            $validated['generator_consumption'] = $validated['generator_consumption'] ?? 0;
            $validated['announcement'] = $validated['announcement'] ?? '';            $validated['free_CUM_per_month'] = $validated['free_CUM_per_month'] ?? 5;
            $validated['miscellaneous'] = $validated['miscellaneous'] ?? 0;
            $validated['connector_damage_with_unknown_person'] = $validated['connector_damage_with_unknown_person'] ?? 0;
            
            $id = DB::table('important_information')->insertGetId($validated);
            
            Log::info('Important information created', ['id' => $id]);
            
            return response()->json([
                'message' => 'Information created successfully',
                'id' => $id,
                'data' => array_merge(['id' => $id], $validated)
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create important information: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create information',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update existing important information record
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'minimum_amount_per_month' => 'required|numeric|min:0',
                'excess_minimum_CUM_per_month' => 'required|numeric|min:0',
                'lossdamage_and_other_charges' => 'nullable|numeric|min:0',
                'electricity_consumption' => 'nullable|numeric|min:0',
                'generator_consumption' => 'nullable|numeric|min:0',                'announcement' => 'nullable|string',
                'free_CUM_per_month' => 'nullable|integer|min:0',
                'miscellaneous' => 'nullable|numeric|min:0',
                'connector_damage_with_unknown_person' => 'nullable|numeric|min:0'
            ]);
            
            // Check if record exists
            $existing = DB::table('important_information')->where('id', $id)->first();
            
            if (!$existing) {
                return response()->json([
                    'message' => 'Record not found'
                ], 404);
            }
            
            // Set defaults for nullable fields
            $validated['lossdamage_and_other_charges'] = $validated['lossdamage_and_other_charges'] ?? 0;
            $validated['electricity_consumption'] = $validated['electricity_consumption'] ?? 0;
            $validated['generator_consumption'] = $validated['generator_consumption'] ?? 0;
            $validated['announcement'] = $validated['announcement'] ?? '';            $validated['free_CUM_per_month'] = $validated['free_CUM_per_month'] ?? 5;
            $validated['miscellaneous'] = $validated['miscellaneous'] ?? 0;
            $validated['connector_damage_with_unknown_person'] = $validated['connector_damage_with_unknown_person'] ?? 0;
            
            DB::table('important_information')
                ->where('id', $id)
                ->update($validated);
            
            Log::info('Important information updated', ['id' => $id, 'updated_fields' => array_keys($validated)]);
            
            // Get updated record
            $updated = DB::table('important_information')->where('id', $id)->first();
            
            return response()->json([
                'message' => 'Information updated successfully',
                'data' => $updated
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update important information: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update information',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get important information as an Eloquent-style model (for use in other controllers)
     */
    public static function getInfo()
    {
        $info = DB::table('important_information')->first();
        
        if (!$info) {
            // Return default object
            return (object) [
                'id' => null,
                'minimum_amount_per_month' => 160.0,
                'excess_minimum_CUM_per_month' => 15.0,
                'lossdamage_and_other_charges' => 0.0,
                'electricity_consumption' => 0.0,
                'generator_consumption' => 0.0,                'announcement' => '',
                'free_CUM_per_month' => 5,
                'miscellaneous' => 0,
                'connector_damage_with_unknown_person' => 0.0
            ];
        }
        
        return $info;
    }
}
