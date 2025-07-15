<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserList;
use App\Models\Place;
use App\Models\ListCategory;
use App\Models\Tag;
use App\Models\Post;

class HomeController extends Controller
{
    public function getFeed(Request $request)
    {
        $user = auth()->user();
        $user->loadCount(['followers', 'following', 'lists']);
        
        // Get latest lists from followed users and self
        $followingIds = $user->following()->pluck('users.id')->toArray();
        $userIds = array_merge([$user->id], $followingIds);
        
        // Get lists, places, and posts with pagination offset
        $page = request()->get('page', 1);
        $offset = ($page - 1) * 3; // 3 items per type per page
        
        $lists = UserList::whereIn('user_id', $userIds)
            ->searchable()
            ->with(['user', 'category', 'tags'])
            ->withCount('items')
            ->latest()
            ->skip($offset)
            ->take(3)
            ->get();
            
        $places = Place::where('status', 'published')
            ->with(['category.parent', 'location'])
            ->latest()
            ->skip($offset)
            ->take(3)
            ->get();
            
        $posts = Post::whereIn('user_id', $userIds)
            ->visible()
            ->with('user')
            ->latest()
            ->skip($offset)
            ->take(4)
            ->get();
            
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
            $feedItems->forPage(1, $perPage), // Already paginated above
            $total + 100, // Approximate total for more pages
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
            
        // Get categories for sidebar
        $categories = ListCategory::where('is_active', true)
            ->withCount(['lists' => function($query) {
                $query->searchable();
            }])
            ->orderBy('sort_order')
            ->limit(10)
            ->get();
            
        // Get trending tags
        $trendingTags = Tag::where('is_active', true)
            ->withCount('lists')
            ->orderBy('lists_count', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'feedItems' => $latestLists, // Unified feed containing both lists and places
            'categories' => $categories,
            'trendingTags' => $trendingTags,
            'user' => $user,
        ]);
    }
    
    public function loadMoreLists(Request $request)
    {
        $user = auth()->user();
        
        // Get latest lists from followed users and self
        $followingIds = $user->following()->pluck('users.id')->toArray();
        $userIds = array_merge([$user->id], $followingIds);
        
        $latestLists = UserList::whereIn('user_id', $userIds)
            ->searchable()
            ->with(['user', 'category', 'tags'])
            ->withCount('items')
            ->latest()
            ->paginate(10);
            
        return response()->json($latestLists);
    }
    
    public function getMetadata(Request $request)
    {
        // Get categories for sidebar
        $categories = ListCategory::where('is_active', true)
            ->withCount(['lists' => function($query) {
                $query->searchable();
            }])
            ->orderBy('sort_order')
            ->limit(10)
            ->get();
            
        // Get trending tags
        $trendingTags = Tag::where('is_active', true)
            ->withCount('lists')
            ->orderBy('lists_count', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'categories' => $categories,
            'trendingTags' => $trendingTags,
        ]);
    }
}