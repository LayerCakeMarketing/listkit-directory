<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserSuspendedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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

    public function suspend(Request $request, User $user)
    {
        // Prevent suspending yourself
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot suspend your own account'], 422);
        }

        // Prevent suspending the last admin
        if ($user->role === 'admin') {
            $activeAdminCount = User::where('role', 'admin')
                ->where('is_suspended', false)
                ->count();
            if ($activeAdminCount <= 1) {
                return response()->json(['error' => 'Cannot suspend the last active admin'], 422);
            }
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'is_suspended' => true,
                'suspended_at' => now(),
                'suspension_reason' => $validated['reason'],
                'suspended_by' => auth()->id(),
            ]);

            // Send suspension notification
            try {
                $user->notify(new UserSuspendedNotification($validated['reason'], auth()->user()->name));
            } catch (\Exception $e) {
                // Log error but don't fail the suspension
                \Log::error('Failed to send suspension notification: ' . $e->getMessage());
            }
        });

        return response()->json([
            'message' => 'User suspended successfully',
            'user' => $user
        ]);
    }

    public function unsuspend(User $user)
    {
        $user->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
            'suspended_by' => null,
        ]);

        return response()->json([
            'message' => 'User unsuspended successfully',
            'user' => $user
        ]);
    }

    public function resendSuspensionEmail(User $user)
    {
        if (!$user->is_suspended) {
            return response()->json(['error' => 'User is not suspended'], 422);
        }

        try {
            $user->notify(new UserSuspendedNotification(
                $user->suspension_reason ?? 'Account suspended',
                User::find($user->suspended_by)?->name
            ));
            return response()->json(['message' => 'Suspension email sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email'], 500);
        }
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

        // Handle related data before deletion
        DB::transaction(function () use ($user) {
            // Get a default admin user to reassign non-nullable foreign keys
            $defaultAdminId = User::where('role', 'admin')
                ->where('id', '!=', $user->id)
                ->orderBy('id')
                ->value('id');
            
            // If no other admin exists, use the first available user
            if (!$defaultAdminId) {
                $defaultAdminId = User::where('id', '!=', $user->id)
                    ->orderBy('id')
                    ->value('id');
            }
            
            // Handle directory_entries - created_by_user_id might be NOT NULL
            if ($defaultAdminId) {
                // Reassign to another user
                DB::table('directory_entries')
                    ->where('created_by_user_id', $user->id)
                    ->update(['created_by_user_id' => $defaultAdminId]);
            } else {
                // If no other users exist, we have to delete the entries
                DB::table('directory_entries')
                    ->where('created_by_user_id', $user->id)
                    ->delete();
            }
            
            // These columns are nullable, so we can set them to null
            DB::table('directory_entries')
                ->where('owner_user_id', $user->id)
                ->update(['owner_user_id' => null]);
                
            DB::table('directory_entries')
                ->where('updated_by_user_id', $user->id)
                ->update(['updated_by_user_id' => null]);
                
            DB::table('directory_entries')
                ->where('rejected_by', $user->id)
                ->update(['rejected_by' => null]);
                
            DB::table('directory_entries')
                ->where('approved_by', $user->id)
                ->update(['approved_by' => null]);
            
            DB::table('directory_entries')
                ->where('ownership_transferred_by', $user->id)
                ->update(['ownership_transferred_by' => null]);
            
            // Handle lists - user_id is nullable in most cases, but handle both scenarios
            try {
                // Try to set to null first (if column is nullable)
                DB::table('lists')
                    ->where('user_id', $user->id)
                    ->update(['user_id' => null]);
            } catch (\Exception $e) {
                // If that fails (NOT NULL constraint), reassign or delete
                if ($defaultAdminId) {
                    DB::table('lists')
                        ->where('user_id', $user->id)
                        ->update(['user_id' => $defaultAdminId]);
                } else {
                    // Delete if no other user exists
                    DB::table('lists')
                        ->where('user_id', $user->id)
                        ->delete();
                }
            }
                
            DB::table('lists')
                ->where('status_changed_by', $user->id)
                ->update(['status_changed_by' => null]);
            
            // Handle polymorphic lists owned by this user - always delete these
            DB::table('lists')
                ->where('owner_type', 'App\\Models\\User')
                ->where('owner_id', $user->id)
                ->delete();
            
            // Handle suspended_by references before deleting user
            DB::table('users')
                ->where('suspended_by', $user->id)
                ->update(['suspended_by' => null]);
            
            // Handle claims - set approval/rejection references to null
            DB::table('claims')
                ->where('approved_by', $user->id)
                ->update(['approved_by' => null]);
            
            DB::table('claims')
                ->where('rejected_by', $user->id)
                ->update(['rejected_by' => null]);
            
            // Delete claims created by this user
            DB::table('claims')
                ->where('user_id', $user->id)
                ->delete();
            
            // Handle claim documents - set verified_by to null
            if (\Schema::hasTable('claim_documents')) {
                DB::table('claim_documents')
                    ->where('verified_by', $user->id)
                    ->update(['verified_by' => null]);
            }
            
            // Handle place_managers - set invited_by to null
            if (\Schema::hasTable('place_managers')) {
                DB::table('place_managers')
                    ->where('invited_by', $user->id)
                    ->update(['invited_by' => null]);
            }
            
            // Delete user's posts
            DB::table('posts')->where('user_id', $user->id)->delete();
            
            // Handle comments - update reply references and delete user's comments
            DB::table('comments')
                ->where('reply_to_user_id', $user->id)
                ->update(['reply_to_user_id' => null]);
                
            DB::table('comments')->where('user_id', $user->id)->delete();
            
            // Delete user's channels
            DB::table('channels')->where('user_id', $user->id)->delete();
            
            // Delete follows
            if (\Schema::hasTable('follows')) {
                DB::table('follows')
                    ->where('follower_id', $user->id)
                    ->orWhere('following_id', $user->id)
                    ->delete();
            }
            
            // Delete user_follows
            if (\Schema::hasTable('user_follows')) {
                DB::table('user_follows')
                    ->where('follower_id', $user->id)
                    ->orWhere('following_id', $user->id)
                    ->delete();
            }
            
            // Delete saved items
            if (\Schema::hasTable('saved_items')) {
                DB::table('saved_items')->where('user_id', $user->id)->delete();
            }
            
            // Delete notifications
            if (\Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', 'App\\Models\\User')
                    ->where('notifiable_id', $user->id)
                    ->delete();
            }
            
            // Delete app_notifications
            if (\Schema::hasTable('app_notifications')) {
                DB::table('app_notifications')->where('recipient_id', $user->id)->delete();
            }
            
            // Finally delete the user
            $user->delete();
        });

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


