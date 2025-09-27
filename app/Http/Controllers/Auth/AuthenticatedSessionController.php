<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticatedSessionController extends Controller
{
    /**
     * Create access and refresh tokens for a user.
     */
    private function createTokens(User $user): array
    {
        // Create an access token (short-lived)
        $accessTokenExpiry = now()->addMinutes((int) config('sanctum.expiration', 60));
        $accessToken = $user->createToken('access-token', ['access'], $accessTokenExpiry)->plainTextToken;

        // Create a refresh token (long-lived)
        $refreshTokenExpiry = now()->addDays(30);
        $refreshToken = $user->createToken('refresh-token', ['refresh'], $refreshTokenExpiry)->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => (int) config('sanctum.expiration', 60) * 60 // in seconds
        ];
    }
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        // Session-based authentication (for web clients)
        // $request->session()->regenerate();
        // return response()->noContent();

        // Token-based authentication (for API clients)
        return response()->json($this->createTokens($user));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        // Session-based logout (for web clients)
        // Auth::guard('web')->logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        // Token-based logout (for API clients) - delete ALL tokens
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Refresh the authenticated user's access token using refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        // Find the refresh token
        $refreshToken = PersonalAccessToken::findToken($request->refresh_token);

        if (!$refreshToken || !$refreshToken->tokenable || $refreshToken->expires_at < now()) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        // Check if token has refresh ability
        if (!$refreshToken->can('refresh')) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = $refreshToken->tokenable;

        // Revoke ALL existing tokens for this user (access and refresh tokens)
        $user->tokens()->delete();

        // Create new tokens
        return response()->json($this->createTokens($user));
    }
}
