<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Models\UserListShare;
use App\Models\User;
use Illuminate\Http\Request;

class ListShareController extends Controller
{
    public function index(UserList $list)
    {
        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to manage shares for this list');
        }

        $shares = $list->shares()
            ->with(['user', 'sharedBy'])
            ->active()
            ->get();

        return response()->json($shares);
    }

    public function store(Request $request, UserList $list)
    {
        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to share this list');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|in:view,edit',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // Can't share with yourself
        if ($validated['user_id'] == auth()->id()) {
            return response()->json(['error' => 'Cannot share list with yourself'], 422);
        }

        $share = $list->shareWith(
            User::find($validated['user_id']),
            $validated['permission'],
            $validated['expires_at']
        );

        $share->load(['user', 'sharedBy']);

        return response()->json([
            'message' => 'List shared successfully',
            'share' => $share
        ], 201);
    }

    public function update(Request $request, UserList $list, UserListShare $share)
    {
        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to manage shares for this list');
        }

        $validated = $request->validate([
            'permission' => 'required|in:view,edit',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $share->update($validated);
        $share->load(['user', 'sharedBy']);

        return response()->json([
            'message' => 'Share updated successfully',
            'share' => $share
        ]);
    }

    public function destroy(UserList $list, UserListShare $share)
    {
        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to manage shares for this list');
        }

        $share->delete();

        return response()->json([
            'message' => 'Share removed successfully'
        ]);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'username', 'email']);

        return response()->json($users);
    }
}