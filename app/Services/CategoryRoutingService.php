<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Place;
use App\Models\Region;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Optimized service for category-based URL routing
 * Handles the new URL structure: /places/{state}/{city}/{parent-category}/{place-slug}-{id}
 */
class CategoryRoutingService
{
    const CACHE_TTL = 3600; // 1 hour cache
    
    /**
     * Get all parent categories for URL routing with their children
     * Cached for performance
     */
    public function getParentCategoriesForRouting()
    {
        return Cache::remember('categories_for_routing', self::CACHE_TTL, function () {
            return Category::forUrlRouting()->get();
        });
    }

    /**
     * Find category by slug, preferring parent categories for URL routing
     * Returns the parent category even if a child category slug is provided
     */
    public function findCategoryForUrl($categorySlug)
    {
        $cacheKey = "category_for_url_{$categorySlug}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->first();
            
            if (!$category) {
                return null;
            }

            // Return parent category for URL consistency
            return $category->getUrlParentCategory();
        });
    }

    /**
     * Optimized query to get places for a specific URL route
     * /places/{state}/{city}/{parent-category}
     */
    public function getPlacesForRoute($stateSlug, $citySlug, $parentCategorySlug, $perPage = 20)
    {
        $cacheKey = "places_route_{$stateSlug}_{$citySlug}_{$parentCategorySlug}_{$perPage}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL / 2, function () use ($stateSlug, $citySlug, $parentCategorySlug, $perPage) {
            // Find regions efficiently
            $state = Region::where('slug', $stateSlug)->where('type', 'state')->first();
            $city = Region::where('slug', $citySlug)
                         ->where('type', 'city')
                         ->where('parent_id', $state?->id)
                         ->first();

            if (!$state || !$city) {
                return collect();
            }

            // Find parent category and get all subcategory IDs
            $parentCategory = Category::where('slug', $parentCategorySlug)
                                    ->whereNull('parent_id')
                                    ->first();

            if (!$parentCategory) {
                return collect();
            }

            // Get all category IDs in this subtree (parent + children)
            $categoryIds = Category::where('path', 'LIKE', $parentCategory->path . '%')
                                 ->pluck('id');

            // Optimized query with proper indexes
            return Place::query()
                ->whereIn('category_id', $categoryIds)
                ->where(function ($q) use ($city) {
                    $q->where('city_region_id', $city->id)
                      ->orWhere('region_id', $city->id);
                })
                ->where('status', 'active')
                ->select([
                    'id', 'title', 'slug', 'description', 'category_id', 
                    'region_id', 'city_region_id', 'featured_image',
                    'price_range', 'is_featured', 'is_verified'
                ])
                ->with([
                    'category:id,name,slug,parent_id',
                    'region:id,name,slug',
                ])
                ->orderBy('is_featured', 'desc')
                ->orderBy('is_verified', 'desc')
                ->orderBy('view_count', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Resolve a place URL and return all route components
     * /places/{state}/{city}/{parent-category}/{place-slug}-{id}
     */
    public function resolveCanonicalPlaceUrl($placeSlugWithId)
    {
        // Extract ID from slug (format: place-name-123)
        if (!preg_match('/^(.+)-(\d+)$/', $placeSlugWithId, $matches)) {
            return null;
        }

        $placeSlug = $matches[1];
        $placeId = $matches[2];

        $cacheKey = "place_url_resolution_{$placeId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($placeId, $placeSlug) {
            $place = Place::with([
                'category.parent',
                'region',
                'cityRegion',
                'stateRegion'
            ])->find($placeId);

            if (!$place || $place->slug !== $placeSlug) {
                return null;
            }

            $parentCategory = $place->category->getUrlParentCategory();
            $city = $place->cityRegion ?? $place->region;
            $state = $place->stateRegion ?? $city?->parent;

            if (!$parentCategory || !$city || !$state) {
                return null;
            }

            return [
                'place' => $place,
                'parent_category' => $parentCategory,
                'city' => $city,
                'state' => $state,
                'canonical_url' => "/places/{$state->slug}/{$city->slug}/{$parentCategory->slug}/{$place->slug}-{$place->id}"
            ];
        });
    }

    /**
     * Generate canonical URL for a place
     */
    public function generateCanonicalUrl(Place $place)
    {
        $parentCategory = $place->category->getUrlParentCategory();
        $city = $place->cityRegion ?? $place->region;
        $state = $place->stateRegion ?? $city?->parent;

        if (!$parentCategory || !$city || !$state) {
            // Fallback to legacy URL structure
            return "/places/{$place->category->slug}/{$place->slug}";
        }

        return "/places/{$state->slug}/{$city->slug}/{$parentCategory->slug}/{$place->slug}-{$place->id}";
    }

    /**
     * Build category hierarchy for navigation/breadcrumbs
     */
    public function getCategoryHierarchy($categorySlug = null)
    {
        $cacheKey = $categorySlug ? "category_hierarchy_{$categorySlug}" : 'category_hierarchy_all';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categorySlug) {
            $query = Category::parentCategories()->withCount([
                'places' => function ($q) {
                    $q->where('status', 'active');
                }
            ]);

            if ($categorySlug) {
                $query->where('slug', $categorySlug);
            }

            return $query->get()->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'path' => $category->path,
                    'places_count' => $category->places_count,
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                            'path' => $child->path,
                            'places_count' => $child->places()->where('status', 'active')->count()
                        ];
                    })
                ];
            });
        });
    }

    /**
     * Clear category-related caches
     */
    public function clearCache($categorySlug = null)
    {
        $patterns = [
            'categories_for_routing',
            'category_hierarchy_all',
        ];

        if ($categorySlug) {
            $patterns[] = "category_for_url_{$categorySlug}";
            $patterns[] = "category_hierarchy_{$categorySlug}";
        }

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Clear place route caches (more complex, but necessary for consistency)
        Cache::flush(); // In production, you'd want more selective cache clearing
    }

    /**
     * Get performance statistics for category queries
     */
    public function getQueryStats()
    {
        return [
            'total_categories' => Category::count(),
            'parent_categories' => Category::whereNull('parent_id')->count(),
            'child_categories' => Category::whereNotNull('parent_id')->count(),
            'max_depth' => Category::max('depth') ?? 0,
            'categories_with_places' => Category::has('places')->count(),
            'avg_places_per_category' => DB::table('categories')
                ->join('directory_entries', 'categories.id', '=', 'directory_entries.category_id')
                ->where('directory_entries.status', 'active')
                ->groupBy('categories.id')
                ->get()
                ->avg(function ($item) {
                    return $item->count ?? 0;
                }) ?? 0
        ];
    }
}