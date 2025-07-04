<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DirectoryEntry;
use App\Models\UserList;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $stats = [
            'users' => User::count(),
            'new_users_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'entries' => DirectoryEntry::count(),
            'pending_entries' => DirectoryEntry::where('status', 'pending_review')->count(),
            'lists' => UserList::count(),
            'public_lists' => UserList::where('is_public', true)->count(),
            'comments' => Comment::count(),
            'comments_today' => Comment::whereDate('created_at', today())->count(),
        ];

        return response()->json($stats);
    }
}