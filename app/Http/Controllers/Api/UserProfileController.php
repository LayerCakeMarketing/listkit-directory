<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Display user by username.
     */
    public function showByUsername($username)
    {
        $user = User::where('custom_url', $username)
            ->orWhere('username', $username)
            ->with('lists')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->custom_url ?? $user->username,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'lists_count' => $user->lists()->public()->count(),
                'created_at' => $user->created_at,
            ]
        ]);
    }
}