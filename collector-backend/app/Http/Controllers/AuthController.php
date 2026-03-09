<?php

// app/Http/Controllers/AuthController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; 

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }        if (!in_array($user->role_id, [1, 2, 3, 4])) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // Update last_login timestamp
        $user->update(['last_login' => now()]);

        // Create token if using Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('role', 'purok'));
    }


    public function createSyncToken(Request $request)
    {
        Log::info('Sync token request received', [
            'username' => $request->input('username'),
            'admin_id' => $request->input('admin_id'),
        ]);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'admin_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find user by username and admin_id
        $user = DB::table('users')
            ->where('username', $request->username)
            ->where('admin_id', $request->admin_id)
            ->first();

        if (!$user) {
            Log::error('User not found', [
                'username' => $request->username,
                'admin_id' => $request->admin_id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'User not found in server database'
            ], 404);
        }

        Log::info('User found', ['admin_id' => $user->admin_id, 'role_id' => $user->role_id]);

        // Check if user has appropriate role (Reader or Super Admin)
        if (!in_array($user->role_id, [1, 4])) {
            Log::error('Insufficient permissions', ['role_id' => $user->role_id]);
            return response()->json([
                'success' => false,
                'message' => 'User does not have sync permissions'
            ], 403);
        }

        // Delete any existing sync tokens for this user
        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\\Models\\User')
            ->where('tokenable_id', $user->admin_id)
            ->where('name', 'sync-token')
            ->delete();

        // Generate plain text token
        $plainTextToken = Str::random(40);
        
        // Hash it for storage (Sanctum uses SHA-256)
        $hashedToken = hash('sha256', $plainTextToken);

        // Insert the token
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $user->admin_id,
            'name' => 'sync-token',
            'token' => $hashedToken,
            'abilities' => '["*"]',  // Changed from json_encode
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Return token in Sanctum format: {tokenable_id}|{plainTextToken}
        $fullToken = $user->admin_id . '|' . $plainTextToken;

        Log::info('Token created successfully', [
            'admin_id' => $user->admin_id,
            'token_preview' => substr($fullToken, 0, 20) . '...'
        ]);

        return response()->json([
            'success' => true,
            'token' => $fullToken,
            'user' => [
                'admin_id' => $user->admin_id,
                'username' => $user->username,
                'fname' => $user->fname,
                'lname' => $user->lname,
            ]
        ]);
    }
}