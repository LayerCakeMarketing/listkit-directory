<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
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
            // Set created_by_user_id to null for directory_entries (places)
            DB::table('directory_entries')
                ->where('created_by_user_id', $user->id)
                ->update(['created_by_user_id' => null]);
            
            // Set owner_user_id to null for owned places
            DB::table('directory_entries')
                ->where('owner_user_id', $user->id)
                ->update(['owner_user_id' => null]);
            
            // Handle lists - set to null or delete
            DB::table('lists')
                ->where('user_id', $user->id)
                ->update(['user_id' => null]);
            
            // Handle polymorphic lists
            DB::table('lists')
                ->where('owner_type', 'App\\Models\\User')
                ->where('owner_id', $user->id)
                ->delete();
            
            // Delete user's posts
            DB::table('posts')->where('user_id', $user->id)->delete();
            
            // Delete user's comments
            DB::table('comments')->where('user_id', $user->id)->delete();
            
            // Delete user's channels
            DB::table('channels')->where('user_id', $user->id)->delete();
            
            // Delete user's claims
            DB::table('claims')->where('user_id', $user->id)->delete();
            
            // Delete user's notifications
            DB::table('app_notifications')->where('recipient_id', $user->id)->delete();
            DB::table('notifications')->where('user_id', $user->id)->delete();
            
            // Delete follows
            DB::table('follows')->where('follower_id', $user->id)->delete();
            DB::table('user_follows')
                ->where('follower_id', $user->id)
                ->orWhere('following_id', $user->id)
                ->delete();
            
            // Delete saved items
            DB::table('saved_items')->where('user_id', $user->id)->delete();
            
            // Finally delete the user
            $user->delete();
        });

        return response()->json(['message' => 'User deleted successfully']);
    }
}