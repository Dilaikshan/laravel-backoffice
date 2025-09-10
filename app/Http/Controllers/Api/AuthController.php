<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User; // We need a local User model for Sanctum
use App\Services\WordPressService;

class AuthController extends Controller
{
    protected $wordpressService;

    public function __construct(WordPressService $wordpressService)
    {
        $this->wordpressService = $wordpressService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email', // Or 'username' if you use WP username
            'password' => 'required|string',
        ]);

        // IMPORTANT: See WordPressService for explanation on how authentication is handled.
        // For this test, we are treating the provided 'email' and 'password'
        // as the WordPress admin's configured username and the Application Password.
        // This is a simplification due to WP.com API limitations for direct user password login.
        $wordpressUserData = $this->wordpressService->authenticateAdminUser(
            $request->email, // Using email as username for consistency, or change to 'username' if preferred
            $request->password
        );

        if (!$wordpressUserData) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records or are not for an administrator.'],
            ]);
        }

        // If authentication successful, create or find a local user.
        // We link our local user to the WordPress admin to manage Sanctum tokens.
        // You could store a 'wordpress_id' on the User model.
        $user = User::firstOrCreate(
            ['email' => $request->email], // Or 'wordpress_id' => $wordpressUserData['ID'] if you add it
            [
                'name' => $wordpressUserData['display_name'] ?? 'WordPress Admin',
                'password' => Hash::make(uniqid()), // Random password, not used for WP auth
            ]
        );

        // Revoke old tokens and create a new one
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'message' => 'Authenticated',
        ]);
    }
}