<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Create a new user
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        event(new Registered($user));

        // Create an access token (short-lived)
        $accessTokenExpiry = now()->addMinutes((int)config('sanctum.expiration', 60));
        $accessToken = $user->createToken('access-token', ['access'], $accessTokenExpiry)->plainTextToken;

        // Create a refresh token (long-lived)
        $refreshTokenExpiry = now()->addDays(30);
        $refreshToken = $user->createToken('refresh-token', ['refresh'], $refreshTokenExpiry)->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => (int)config('sanctum.expiration', 60) * 60 // in seconds
        ]);
    }
}
