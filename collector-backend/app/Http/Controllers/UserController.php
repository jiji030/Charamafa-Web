<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.role_id')
            ->leftJoin('puroks', 'users.purok_id', '=', 'puroks.purok_id')
            ->select('users.*', 'roles.role_name', 'puroks.purok as purok_name')
            ->orderBy('users.admin_id', 'asc')
            ->get();

        return response()->json($users);
    }

    public function show($id)
    {
        $user = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.role_id')
            ->leftJoin('puroks', 'users.purok_id', '=', 'puroks.purok_id')
            ->select('users.*', 'roles.role_name', 'puroks.purok as purok_name')
            ->where('users.admin_id', $id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'contact_no' => 'nullable|string|max:20',
            'username' => 'required|string|unique:users,username,' . $id . ',admin_id',
            'purok_id' => 'nullable|integer|exists:puroks,purok_id',
            'role_id' => 'required|integer|exists:roles,role_id',
            'password' => 'nullable|string|min:4'
        ]);

        try {
            $updateData = [
                'fname' => $validated['fname'],
                'mname' => $validated['mname'],
                'lname' => $validated['lname'],
                'suffix' => $validated['suffix'],
                'contact_no' => $validated['contact_no'],
                'username' => $validated['username'],
                'purok_id' => $validated['purok_id'],
                'role_id' => $validated['role_id']
            ];

            // Only update password if provided
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            DB::table('users')
                ->where('admin_id', $id)
                ->update($updateData);

            return response()->json([
                'message' => 'User updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'lname' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'contact_no' => 'nullable|string|max:20',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:4',
            'purok_id' => 'nullable|integer|exists:puroks,purok_id',
            'role_id' => 'required|integer|exists:roles,role_id',
            'association_id' => 'nullable|integer'
        ]);        // Hash the password
        $validated['password'] = Hash::make($validated['password']);
        
        // Set current timestamp for creation
        $validated['last_date_synced'] = now();

        try {
            $userId = DB::table('users')->insertGetId($validated);
            
            // Get the created user with role and purok information
            $user = DB::table('users')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.role_id')
                ->leftJoin('puroks', 'users.purok_id', '=', 'puroks.purok_id')
                ->select('users.*', 'roles.role_name', 'puroks.purok as purok_name')
                ->where('users.admin_id', $userId)
                ->first();

            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create user', 'error' => $e->getMessage()], 500);
        }
    }

    public function getRoles()
    {
        $roles = DB::table('roles')->orderBy('role_id', 'asc')->get();
        return response()->json($roles);
    }
    
    public function destroy($id)
    {
        DB::table('users')->where('admin_id', $id)->delete();
        
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}