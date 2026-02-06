<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            // role defaults to 'user' in migration
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * POST /api/auth/login
     * Body: email, password
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Optional: revoke existing tokens on login
        $user->tokens()->delete();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * GET /api/auth/me
     * Header: Authorization: Bearer <token>
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * POST /api/auth/logout
     * Header: Authorization: Bearer <token>
     *
     * Revokes only the current access token.
     */
    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        abort_unless($token, 401);

        $token->delete();
        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    /**
     * POST /api/auth/logout-all (optional)
     * Revokes all tokens for the user.
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();
        $user?->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices',
        ]);
    }
}
