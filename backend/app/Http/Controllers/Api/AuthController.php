<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Login user
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => Auth::user(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    /**
     * Register user
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = \App\Models\User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $user,
        ], 201);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        Auth::logout();
        return response()->json(['success' => true, 'message' => 'Logout successful']);
    }
}
