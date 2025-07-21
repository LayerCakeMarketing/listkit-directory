<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|in:admin,manager,editor,business_owner,user',
            'sort_by' => 'nullable|in:name,email,created_at,last_active_at',
            'sort_order' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::query();

        // Search by name, email, or username
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Get users WITHOUT the problematic withCount for now
        $users = $query->paginate($request->get('limit', 20));

        // Add default counts to avoid frontend errors
        $users->getCollection()->transform(function ($user) {
            $user->lists_count = 0;
            $user->created_entries_count = 0;
            $user->owned_entries_count = 0;
            return $user;
        });

        return response()->json($users);
    }

    public function stats()
    {
        $stats = [
            'total_users' => User::count(),
            'by_role' => User::selectRaw('role, count(*) as count')
                            ->groupBy('role')
                            ->pluck('count', 'role'),
            'active_today' => User::where('last_active_at', '>=', now()->subDay())->count(),
            'active_this_week' => User::where('last_active_at', '>=', now()->subWeek())->count(),
            'new_this_month' => User::where('created_at', '>=', now()->subMonth())->count(),
        ];

        return response()->json($stats);
    }

    public function show(User $user)
    {
        // Simplified version without relationships
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'firstname' => 'sometimes|required|string|max:255',
            'lastname' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'username' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'sometimes|required|in:admin,manager,editor,business_owner,user',
            'is_public' => 'sometimes|boolean',
            'bio' => 'nullable|string|max:1000',
        ]);

        // Update the name field if firstname or lastname is provided
        if (isset($validated['firstname']) || isset($validated['lastname'])) {
            $firstname = $validated['firstname'] ?? $user->firstname;
            $lastname = $validated['lastname'] ?? $user->lastname;
            $validated['name'] = trim($firstname . ' ' . $lastname);
        }

        // Prevent removing the last admin
        if ($user->role === 'admin' && isset($validated['role']) && $validated['role'] !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['error' => 'Cannot remove the last admin'], 422);
            }
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot delete your own account'], 422);
        }

        // Prevent deleting the last admin
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['error' => 'Cannot delete the last admin'], 422);
            }
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'role' => 'nullable|in:admin,manager,editor,business_owner,user',
            'action' => 'required|in:update_role,delete',
        ]);

        $userIds = $validated['user_ids'];
        
        // Remove current user from bulk operations
        $userIds = array_filter($userIds, fn($id) => $id != auth()->id());

        if ($validated['action'] === 'update_role' && isset($validated['role'])) {
            User::whereIn('id', $userIds)->update(['role' => $validated['role']]);
            return response()->json(['message' => 'Roles updated successfully']);
        }

        if ($validated['action'] === 'delete') {
            // Don't delete admins in bulk
            User::whereIn('id', $userIds)->where('role', '!=', 'admin')->delete();
            return response()->json(['message' => 'Users deleted successfully']);
        }

        return response()->json(['error' => 'Invalid action'], 422);
    }
    public function store(Request $request)
{
    $validated = $request->validate([
        'firstname' => 'required|string|max:255',
        'lastname' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'role' => 'required|in:admin,manager,editor,business_owner,user',
        'username' => 'nullable|string|max:255|unique:users',
    ]);

    // Create the name field from firstname and lastname
    $name = trim($validated['firstname'] . ' ' . $validated['lastname']);

    $user = User::create([
        'name' => $name,
        'firstname' => $validated['firstname'],
        'lastname' => $validated['lastname'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => $validated['role'],
        'username' => $validated['username'] ?? null,
    ]);

    return response()->json([
        'message' => 'User created successfully',
        'user' => $user
    ], 201);
}
}


