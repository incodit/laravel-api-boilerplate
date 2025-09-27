<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset code to the user's email.
     */
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->input('email');
        
        // Find user by encrypted email
        $emailHash = hash_hmac('sha256', mb_strtolower(trim($email)), config('app.key'));
        $user = User::where('email_x', $emailHash)->first();

        if (!$user) {
            return response()->json([
                'message' => 'We can\'t find a user with that email address.'
            ], 404);
        }

        try {
            // Generate 6-digit code
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store in password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'email' => $email,
                    'token' => Hash::make($code),
                    'created_at' => now()
                ]
            );

            // Send notification
            $user->notify(new PasswordResetCodeNotification($code));

            return response()->json([
                'message' => 'Password reset code sent to your email.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to send password reset code.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password using the code.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = $request->input('email');
        $code = $request->input('code');
        
        // Find user by encrypted email
        $emailHash = hash_hmac('sha256', mb_strtolower(trim($email)), config('app.key'));
        $user = User::where('email_x', $emailHash)->first();

        if (!$user) {
            return response()->json([
                'message' => 'We can\'t find a user with that email address.'
            ], 404);
        }

        // Check if reset token exists and is valid
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => 'Invalid or expired reset code.'
            ], 400);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'message' => 'Reset code has expired.'
            ], 400);
        }

        // Verify the code
        if (!Hash::check($code, $resetRecord->token)) {
            return response()->json([
                'message' => 'Invalid reset code.'
            ], 400);
        }

        try {
            // Update password
            $user->update([
                'password' => Hash::make($request->password),
                'remember_token' => null,
            ]);

            // Delete the reset token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Revoke all existing tokens
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Password has been reset successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to reset password.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}