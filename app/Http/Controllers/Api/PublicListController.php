<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Models\ListCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicListController extends Controller
{
    /**
     * Get public lists with caching
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 12);
        $categoryId = $request->get('category_id');
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Create cache key based on parameters
        $cacheKey = 'public_lists:' . md5(serialize([
            'page' => $page,
            'per_page' => $perPage,
            'category_id' => $categoryId,
            'search' => $search,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ]));
        
        // Cache for 5 minutes
        return Cache::remember($cacheKey, 300, function () use ($request, $perPage, $categoryId, $search, $sortBy, $sortOrder) {
            $query = UserList::searchable()
                ->notOnHold()
                ->with([
                    'user:id,name,username,custom_url,avatar',
                    'owner', // Load full owner (polymorphic - could be User or Channel)
                    'category:id,name,slug,color'
                ])
                ->withCount('items');
            
            // Apply filters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }
            
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
            
            // Apply sorting
            switch ($sortBy) {
                case 'popularity':
                    $query->orderBy('views_count', $sortOrder);
                    break;
                case 'items_count':
                    $query->orderBy('items_count', $sortOrder);
                    break;
                case 'title':
                    $query->orderBy('name', $sortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $sortOrder);
                    break;
                default:
                    $query->orderBy('updated_at', $sortOrder);
            }
            
            return $query->paginate($perPage);
        });
    }
    
    /**
     * Get categories with list counts (cached)
     */
    public function categories()
    {
        // Cache for 30 minutes
        return Cache::remember('public_list_categories', 1800, function () {
            return ListCategory::active()
                ->ordered()
                ->select('id', 'name', 'slug', 'color', 'svg_icon')
                ->withCount(['lists' => function ($query) {
                    $query->searchable()->notOnHold();
                }])
                ->having('lists_count', '>', 0)
                ->get();
        });
    }
    
    /**
     * Clear cache when lists are updated
     */
    public static function clearCache()
    {
        Cache::forget('public_list_categories');
        // Clear paginated caches
        Cache::flush(); // In production, use tags instead
    }
}