<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserList;
use App\Models\ListCategory;
use App\Models\Tag;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function getFeed(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            // Load counts manually to avoid polymorphic issues
            $user->loadCount(['followers', 'following']);
            $user->lists_count = $user->lists()->count();
            
            // Get latest lists from followed users and self
            $followingUserIds = $user->followingUsers()->pluck('users.id')->toArray();
            $userIds = array_merge([$user->id], $followingUserIds);
            
            // Get lists, places, and posts with pagination offset
            $page = request()->get('page', 1);
            $offset = ($page - 1) * 3; // 3 items per type per page
            
            // Get user lists using polymorphic relationship
            $lists = UserList::where(function($query) use ($userIds) {
                    $query->where('owner_type', 'App\Models\User')
                          ->whereIn('owner_id', $userIds);
                })
                ->orWhere(function($query) use ($userIds) {
                    // Also include legacy lists without owner_type
                    $query->whereNull('owner_type')
                          ->whereIn('user_id', $userIds);
                })
                ->where('visibility', 'public')
                ->select(['id', 'name', 'slug', 'owner_type', 'owner_id', 'user_id', 'category_id', 'visibility', 'featured_image', 'featured_image_cloudflare_id', 'view_count', 'created_at', 'updated_at'])
                ->with(['owner', 'user', 'category', 'tags'])
                ->withCount('items')
                ->latest()
                ->skip($offset)
                ->take(3)
                ->get();
            
            // Initialize empty collections for places and posts
            $places = collect();
            $posts = collect();
            
            // Only get places if the table and model exist
            if (Schema::hasTable('places') && class_exists('App\Models\Place')) {
                $places = \App\Models\Place::where('status', 'published')
                    ->select(['id', 'title', 'slug', 'category_id', 'featured_image', 'status', 'created_at', 'updated_at'])
                    ->with(['category', 'location'])
                    ->latest()
                    ->skip($offset)
                    ->take(3)
                    ->get();
            }
            
            // Only get posts if the table and model exist
            if (Schema::hasTable('posts') && class_exists('App\Models\Post')) {
                $posts = \App\Models\Post::whereIn('user_id', $userIds)
                    ->visible() // Use the visible scope which checks expiration
                    ->with(['user', 'tags'])
                    ->latest()
                    ->skip($offset)
                    ->take(4)
                    ->get();
            }
            
            // Add type identifier to each item
            $lists->each(function ($list) {
                $list->feed_type = 'list';
                $list->feed_timestamp = $list->created_at;
                
                // Ensure user has name field populated
                if ($list->user && !$list->user->name && ($list->user->firstname || $list->user->lastname)) {
                    $list->user->name = trim($list->user->firstname . ' ' . $list->user->lastname);
                }
                // Also check owner if it's different from user
                if ($list->owner && !$list->owner->name && ($list->owner->firstname || $list->owner->lastname)) {
                    $list->owner->name = trim($list->owner->firstname . ' ' . $list->owner->lastname);
                }
            });
            
            $places->each(function ($place) {
                $place->feed_type = 'place';
                $place->feed_timestamp = $place->created_at;
            });
            
            $posts->each(function ($post) {
                $post->feed_type = 'post';
                $post->feed_timestamp = $post->created_at;
                
                // Ensure user has name field populated
                if ($post->user && !$post->user->name && ($post->user->firstname || $post->user->lastname)) {
                    $post->user->name = trim($post->user->firstname . ' ' . $post->user->lastname);
                }
            });
            
            // Merge and sort by timestamp
            $feedItems = collect([$lists, $places, $posts])
                ->flatten()
                ->sortByDesc('feed_timestamp')
                ->values();
            
            // Create pagination-like structure
            $perPage = 10;
            $total = $feedItems->count();
            
            $latestLists = new \Illuminate\Pagination\LengthAwarePaginator(
                $feedItems->forPage(1, $perPage),
                $total + 100, // Approximate total for more pages
                $perPage,
                $page,
                ['path' => request()->url(), 'pageName' => 'page']
            );
            
            // Get categories for sidebar
            $categories = ListCategory::where('is_active', true)
                ->withCount(['lists' => function($query) {
                    $query->where('visibility', 'public');
                }])
                ->orderBy('sort_order')
                ->limit(10)
                ->get();
            
            // Get trending tags
            $trendingTags = collect();
            if (Schema::hasTable('tags')) {
                $trendingTags = Tag::orderBy('usage_count', 'desc')
                    ->limit(10)
                    ->get();
            }
            
            return response()->json([
                'feedItems' => $latestLists,
                'categories' => $categories,
                'trendingTags' => $trendingTags,
                'user' => $user,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('HomeController getFeed error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load feed',
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
    
    public function loadMoreLists(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            // Get latest lists from followed users and self
            $followingUserIds = $user->followingUsers()->pluck('users.id')->toArray();
            $userIds = array_merge([$user->id], $followingUserIds);
            
            // Get lists using polymorphic relationship
            $latestLists = UserList::where(function($query) use ($userIds) {
                    $query->where('owner_type', 'App\Models\User')
                          ->whereIn('owner_id', $userIds);
                })
                ->orWhere(function($query) use ($userIds) {
                    // Also include legacy lists without owner_type
                    $query->whereNull('owner_type')
                          ->whereIn('user_id', $userIds);
                })
                ->where('visibility', 'public')
                ->select(['id', 'name', 'slug', 'owner_type', 'owner_id', 'user_id', 'category_id', 'visibility', 'featured_image', 'featured_image_cloudflare_id', 'view_count', 'created_at', 'updated_at'])
                ->with(['owner', 'user', 'category', 'tags'])
                ->withCount('items')
                ->latest()
                ->paginate(10);
            
            return response()->json($latestLists);
            
        } catch (\Exception $e) {
            \Log::error('HomeController loadMoreLists error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to load more lists',
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
    
    public function getMetadata(Request $request)
    {
        try {
            // Get categories for sidebar
            $categories = ListCategory::where('is_active', true)
                ->withCount(['lists' => function($query) {
                    $query->where('visibility', 'public');
                }])
                ->orderBy('sort_order')
                ->limit(10)
                ->get();
            
            // Get trending tags
            $trendingTags = collect();
            if (Schema::hasTable('tags')) {
                $trendingTags = Tag::orderBy('usage_count', 'desc')
                    ->limit(10)
                    ->get();
            }
            
            return response()->json([
                'categories' => $categories,
                'trendingTags' => $trendingTags,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('HomeController getMetadata error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to load metadata',
                'message' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}