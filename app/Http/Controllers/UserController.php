<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Set user avatar.
     */
    public function setAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,webp|max:2048'
        ]);

        $user = $request->user();

        try {
            // Upload new avatar to S3 - singleFile() config will auto-replace old ones
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar', 's3');

            return response()->json([
                'message' => 'Avatar updated successfully',
                'avatar_url' => $user->fresh()->avatar
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
