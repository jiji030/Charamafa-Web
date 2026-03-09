<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidateSyncToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            Log::error('No token provided in request');
            return response()->json([
                'success' => false,
                'message' => 'No token provided'
            ], 401);
        }

        // Parse token format: {tokenable_id}|{plainTextToken}
        $parts = explode('|', $token, 2);
        
        if (count($parts) !== 2) {
            Log::error('Invalid token format', ['token' => substr($token, 0, 20) . '...']);
            return response()->json([
                'success' => false,
                'message' => 'Invalid token format'
            ], 401);
        }

        [$tokenableId, $plainTextToken] = $parts;
        
        // Hash the plain text token
        $hashedToken = hash('sha256', $plainTextToken);

        // Find the token in database
        $tokenRecord = DB::table('personal_access_tokens')
            ->where('tokenable_id', $tokenableId)
            ->where('token', $hashedToken)
            ->first();

        if (!$tokenRecord) {
            Log::error('Token not found in database', [
                'tokenable_id' => $tokenableId,
                'hashed_token' => substr($hashedToken, 0, 20) . '...'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        Log::info('Token validated successfully', ['tokenable_id' => $tokenableId]);

        // Optionally, update last_used_at
        DB::table('personal_access_tokens')
            ->where('id', $tokenRecord->id)
            ->update(['last_used_at' => now()]);

        // Token is valid, continue
        return $next($request);
    }
}