<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserList;
use App\Models\ListCategory;
use App\Models\Tag;
use Illuminate\Support\Facades\Schema;

class HomeControllerFixed extends Controller
{
    public function getFeed(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            $user->loadCount(['followers', 'following', 'lists']);
            
            // Get latest lists from followed users and self
            $followingIds = $user->following()->pluck('users.id')->toArray();
            $userIds = array_merge([$user->id], $followingIds);
            
            // Get lists, places, and posts with pagination offset
            $page = request()->get('page', 1);
            $offset = ($page - 1) * 3; // 3 items per type per page
            
            // Get user lists
            $lists = UserList::whereIn('user_id', $userIds)
                ->where('visibility', 'public')
                ->with(['user', 'category', 'tags'])
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
                    ->with(['category', 'location'])
                    ->latest()
                    ->skip($offset)
                    ->take(3)
                    ->get();
            }
            
            // Only get posts if the table and model exist
            if (Schema::hasTable('posts') && class_exists('App\Models\Post')) {
                $posts = \App\Models\Post::whereIn('user_id', $userIds)
                    ->where('status', 'published')
                    ->where(function($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->with('user')
                    ->latest()
                    ->skip($offset)
                    ->take(4)
                    ->get();
            }
            
            // Add type identifier to each item
            $lists->each(function ($list) {
                $list->feed_type = 'list';
                $list->feed_timestamp = $list->created_at;
            });
            
            $places->each(function ($place) {
                $place->feed_type = 'place';
                $place->feed_timestamp = $place->created_at;
            });
            
            $posts->each(function ($post) {
                $post->feed_type = 'post';
                $post->feed_timestamp = $post->created_at;
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
                $trendingTags = Tag::where('is_active', true)
                    ->withCount('lists')
                    ->orderBy('lists_count', 'desc')
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
            $followingIds = $user->following()->pluck('users.id')->toArray();
            $userIds = array_merge([$user->id], $followingIds);
            
            $latestLists = UserList::whereIn('user_id', $userIds)
                ->where('visibility', 'public')
                ->with(['user', 'category', 'tags'])
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
                $trendingTags = Tag::where('is_active', true)
                    ->withCount('lists')
                    ->orderBy('lists_count', 'desc')
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