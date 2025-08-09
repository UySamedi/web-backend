<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user (Admin or Student)
     */
    public function register(Request $request) {
    // Validate incoming request without 'role'
    $fields = $request->validate([
        'name' => 'required|string',
        'email' => 'required|string|email|unique:users,email',
        'password' => 'required|string|confirmed|min:6',
    ]);

    // Create user with role = 'student' by default
    $user = User::create([
        'name' => $fields['name'],
        'email' => $fields['email'],
        'password' => bcrypt($fields['password']),
        'role' => 'student', // fixed role as 'student'
    ]);

    // Create API token
    $token = $user->createToken('apptoken')->plainTextToken;

    return response()->json([
        'message' => 'Registration successful',
        'user' => $user,
        'token' => $token
    ], 201);
}


    /**
     * Login an existing user
     */
    public function login(Request $request) {
        // ✅ Validate login fields
        $fields = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // ✅ Find user by email
        $user = User::where('email', $fields['email'])->first();

        // ✅ Check credentials
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // ✅ (Optional) Ensure correct role integrity (for debugging mismatch issues)
        if (!in_array($user->role, ['admin', 'student'])) {
            return response()->json(['message' => 'User role is invalid'], 403);
        }

        // ✅ Generate token
        $token = $user->createToken('apptoken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }
}
