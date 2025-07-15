<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Models\User;
use Illuminate\Http\Request;

class ListManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = UserList::with(['user', 'items', 'category']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }
        
        // Search by user
        if ($request->filled('user_search')) {
            $userSearch = $request->user_search;
            $query->whereHas('user', function ($userQuery) use ($userSearch) {
                $userQuery->where('name', 'like', "%{$userSearch}%")
                          ->orWhere('email', 'like', "%{$userSearch}%")
                          ->orWhere('username', 'like', "%{$userSearch}%");
            });
        }

        // Filter by visibility
        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('status', $request->status);
            }
        }
        
        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'user_name') {
            $query->join('users', 'lists.user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('lists.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $lists = $query->paginate(20);

        // Transform the data to include user custom URL
        $lists->getCollection()->transform(function ($list) {
            return [
                'id' => $list->id,
                'name' => $list->name,
                'slug' => $list->slug,
                'description' => $list->description,
                'visibility' => $list->visibility,
                'status' => $list->status ?: 'active',
                'status_reason' => $list->status_reason,
                'status_changed_at' => $list->status_changed_at,
                'view_count' => $list->view_count,
                'items_count' => $list->items->count(),
                'is_featured' => $list->is_featured,
                'created_at' => $list->created_at,
                'updated_at' => $list->updated_at,
                'featured_image_url' => $list->featured_image_url,
                'image_url' => $list->featured_image_url,
                'is_public' => $list->visibility === 'public',
                'has_content' => !empty($list->description),
                'views' => $list->view_count,
                'likes' => 0,
                'category' => $list->category ? [
                    'id' => $list->category->id,
                    'name' => $list->category->name,
                ] : null,
                'user' => $list->user ? [
                    'id' => $list->user->id,
                    'name' => $list->user->name,
                    'username' => $list->user->username,
                    'custom_url' => $list->user->custom_url,
                    'email' => $list->user->email,
                    'avatar' => $list->user->avatar_url,
                    'profile_url' => '/' . ($list->user->custom_url ?? $list->user->username),
                ] : null,
                'list_url' => $list->user ? ('/' . ($list->user->custom_url ?? $list->user->username) . '/' . $list->slug) : null,
            ];
        });

        return response()->json($lists);
    }

    public function stats()
    {
        return response()->json([
            'total' => UserList::count(),
            'public' => UserList::where('visibility', 'public')->count(),
            'private' => UserList::where('visibility', 'private')->count(),
            'featured' => UserList::where('is_featured', true)->count(),
            'avg_items' => round(UserList::withCount('items')->get()->avg('items_count'), 1),
            'total_items' => \App\Models\ListItem::count(),
            'on_hold' => UserList::whereNotNull('status')->where('status', 'on_hold')->count(),
        ]);
    }

    public function show($id)
    {
        $list = UserList::with(['user', 'items.directoryEntry'])->findOrFail($id);
        
        return response()->json([
            'id' => $list->id,
            'name' => $list->name,
            'slug' => $list->slug,
            'description' => $list->description,
            'visibility' => $list->visibility,
            'status' => $list->status ?: 'active',
            'status_reason' => $list->status_reason,
            'status_changed_at' => $list->status_changed_at,
            'view_count' => $list->view_count,
            'is_featured' => $list->is_featured,
            'created_at' => $list->created_at,
            'updated_at' => $list->updated_at,
            'user' => [
                'id' => $list->user->id,
                'name' => $list->user->name,
                'username' => $list->user->username,
                'custom_url' => $list->user->custom_url,
                'profile_url' => '/' . ($list->user->custom_url ?? $list->user->username),
            ],
            'items' => $list->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'order_index' => $item->order_index,
                    'notes' => $item->notes,
                    'affiliate_url' => $item->affiliate_url,
                    'entry' => $item->directoryEntry ? [
                        'id' => $item->directoryEntry->id,
                        'title' => $item->directoryEntry->title,
                        'description' => $item->directoryEntry->description,
                        'logo_url' => $item->directoryEntry->logo_url,
                    ] : null,
                ];
            }),
        ]);
    }

    public function update(Request $request, $id)
    {
        $list = UserList::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'sometimes|required|in:public,private,unlisted',
            'is_featured' => 'sometimes|boolean',
        ]);

        $list->update($validated);

        return response()->json([
            'message' => 'List updated successfully',
            'list' => $list,
        ]);
    }

    public function destroy($id)
    {
        $list = UserList::findOrFail($id);
        
        // Delete all list items first
        $list->items()->delete();
        
        // Delete the list
        $list->delete();

        return response()->json([
            'message' => 'List deleted successfully',
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'list_ids' => 'required|array',
            'list_ids.*' => 'exists:lists,id',
            'action' => 'required|string|in:delete,update_visibility,toggle_featured,update_status',
            'visibility' => 'required_if:action,update_visibility|in:public,private,unlisted',
            'is_featured' => 'required_if:action,toggle_featured|boolean',
            'status' => 'required_if:action,update_status|in:active,on_hold,draft',
            'reason' => 'required_if:status,on_hold|nullable|string',
        ]);

        $lists = UserList::whereIn('id', $validated['list_ids'])->get();

        switch ($validated['action']) {
            case 'delete':
                foreach ($lists as $list) {
                    $list->items()->delete();
                    $list->delete();
                }
                $message = 'Lists deleted successfully';
                break;

            case 'update_visibility':
                UserList::whereIn('id', $validated['list_ids'])
                    ->update(['visibility' => $validated['visibility']]);
                $message = 'Visibility updated successfully';
                break;

            case 'toggle_featured':
                UserList::whereIn('id', $validated['list_ids'])
                    ->update(['is_featured' => $validated['is_featured']]);
                $message = 'Featured status updated successfully';
                break;

            case 'update_status':
                $updateData = [
                    'status' => $validated['status'],
                    'status_changed_at' => now(),
                    'status_changed_by' => auth()->id()
                ];
                
                if ($validated['status'] === 'on_hold') {
                    $updateData['status_reason'] = $validated['reason'] ?? 'TOS violation';
                } else {
                    $updateData['status_reason'] = null;
                }
                
                UserList::whereIn('id', $validated['list_ids'])->update($updateData);
                $message = 'Status updated successfully';
                break;

            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }

        return response()->json(['message' => $message]);
    }

    public function updateStatus(Request $request, $id)
    {
        $list = UserList::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:active,on_hold,draft',
            'reason' => 'required_if:status,on_hold|nullable|string|max:500',
        ]);
        
        $updateData = [
            'status' => $validated['status'],
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id()
        ];
        
        if ($validated['status'] === 'on_hold') {
            $updateData['status_reason'] = $validated['reason'];
        } else {
            $updateData['status_reason'] = null;
        }
        
        $list->update($updateData);
        
        return response()->json([
            'message' => 'List status updated successfully',
            'list' => $list
        ]);
    }
}