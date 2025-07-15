<?php

namespace App\Services;

use App\Models\Region;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RegionService
{
    /**
     * Cache duration in minutes
     */
    protected $cacheDuration = 60 * 24; // 24 hours

    /**
     * Get region data with caching
     */
    public function getRegion($identifier, $bySlug = true)
    {
        $cacheKey = $bySlug ? "region_slug_{$identifier}" : "region_id_{$identifier}";
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($identifier, $bySlug) {
            $query = Region::with(['parent', 'children']);
            
            if ($bySlug) {
                $query->where('slug', $identifier);
            } else {
                $query->where('id', $identifier);
            }
            
            return $query->first();
        });
    }

    /**
     * Get region by path (e.g., ['california', 'los-angeles', 'hollywood'])
     */
    public function getRegionByPath(array $slugs)
    {
        if (empty($slugs)) {
            return null;
        }

        $cacheKey = 'region_path_' . implode('_', $slugs);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($slugs) {
            $region = null;
            $parent = null;

            foreach ($slugs as $slug) {
                $query = Region::where('slug', $slug);
                
                if ($parent) {
                    $query->where('parent_id', $parent->id);
                }
                
                $region = $query->first();
                
                if (!$region) {
                    return null;
                }
                
                $parent = $region;
            }
            
            return $region;
        });
    }

    /**
     * Get featured entries for a region with caching
     */
    public function getFeaturedEntries(Region $region, $limit = null)
    {
        $cacheKey = $region->getCacheKey('featured') . ($limit ? "_limit_{$limit}" : '');
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($region, $limit) {
            $query = $region->featuredEntries()
                ->with(['location', 'category', 'stateRegion', 'cityRegion', 'neighborhoodRegion'])
                ->published();
            
            if ($limit) {
                $query->limit($limit);
            }
            
            return $query->get();
        });
    }

    /**
     * Get all entries for a region with caching and pagination
     */
    public function getRegionEntries(Region $region, $perPage = 20, $filters = [])
    {
        // For paginated results, we'll use a shorter cache time
        $cacheKey = $region->getCacheKey('entries') . '_page_' . request()->get('page', 1) . '_' . md5(json_encode($filters));
        $shortCacheDuration = 60; // 1 hour for paginated results
        
        return Cache::remember($cacheKey, $shortCacheDuration, function () use ($region, $perPage, $filters) {
            $query = $region->directoryEntries()
                ->with(['location', 'category'])
                ->published();
            
            // Apply filters
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            
            if (!empty($filters['is_featured'])) {
                $query->where('is_featured', true);
            }
            
            if (!empty($filters['is_verified'])) {
                $query->where('is_verified', true);
            }
            
            // Sorting
            $sortBy = $filters['sort_by'] ?? 'featured_and_recent';
            
            switch ($sortBy) {
                case 'recent':
                    $query->orderBy('published_at', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('view_count', 'desc');
                    break;
                case 'alphabetical':
                    $query->orderBy('title', 'asc');
                    break;
                case 'featured_and_recent':
                default:
                    $query->orderBy('is_featured', 'desc')
                          ->orderBy('published_at', 'desc');
                    break;
            }
            
            return $query->paginate($perPage);
        });
    }

    /**
     * Get child regions with caching
     */
    public function getChildRegions(Region $region)
    {
        $cacheKey = $region->getCacheKey('children');
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($region) {
            return $region->children()
                ->withCount(['stateEntries', 'cityEntries', 'neighborhoodEntries'])
                ->orderBy('display_priority', 'desc')
                ->orderBy('name', 'asc')
                ->get();
        });
    }

    /**
     * Get featured regions
     */
    public function getFeaturedRegions($level = null, $limit = 10)
    {
        $cacheKey = 'featured_regions' . ($level ? "_level_{$level}" : '') . "_limit_{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($level, $limit) {
            $query = Region::featured();
            
            if ($level !== null) {
                $query->where('level', $level);
            }
            
            return $query->limit($limit)->get();
        });
    }

    /**
     * Search regions
     */
    public function searchRegions($search, $level = null, $limit = 10)
    {
        $cacheKey = 'search_regions_' . md5($search) . ($level ? "_level_{$level}" : '') . "_limit_{$limit}";
        
        return Cache::remember($cacheKey, 60, function () use ($search, $level, $limit) {
            $query = Region::where('name', 'ILIKE', '%' . $search . '%');
            
            if ($level !== null) {
                $query->where('level', $level);
            }
            
            return $query->orderBy('cached_place_count', 'desc')
                        ->limit($limit)
                        ->get();
        });
    }

    /**
     * Get region statistics
     */
    public function getRegionStats(Region $region)
    {
        $cacheKey = $region->getCacheKey('stats');
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($region) {
            return [
                'total_places' => $region->cached_place_count,
                'featured_places' => $region->featuredEntries()->count(),
                'child_regions' => $region->children()->count(),
                'categories' => DB::table('directory_entries')
                    ->join('categories', 'directory_entries.category_id', '=', 'categories.id')
                    ->where(function ($query) use ($region) {
                        $query->where('directory_entries.state_region_id', $region->id)
                              ->orWhere('directory_entries.city_region_id', $region->id)
                              ->orWhere('directory_entries.neighborhood_region_id', $region->id);
                    })
                    ->where('directory_entries.status', 'published')
                    ->select('categories.id', 'categories.name', DB::raw('COUNT(*) as count'))
                    ->groupBy('categories.id', 'categories.name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
            ];
        });
    }

    /**
     * Update featured entries for a region
     */
    public function updateFeaturedEntries(Region $region, array $entryData)
    {
        DB::transaction(function () use ($region, $entryData) {
            // Clear existing featured entries
            $region->allFeaturedEntries()->detach();
            
            // Add new featured entries
            foreach ($entryData as $index => $data) {
                $region->allFeaturedEntries()->attach($data['directory_entry_id'], [
                    'priority' => $data['priority'] ?? $index,
                    'label' => $data['label'] ?? null,
                    'tagline' => $data['tagline'] ?? null,
                    'custom_data' => $data['custom_data'] ?? null,
                    'featured_until' => $data['featured_until'] ?? null,
                    'is_active' => $data['is_active'] ?? true
                ]);
            }
        });
        
        // Clear cache
        $region->clearCache();
    }

    /**
     * Clear all region caches
     */
    public function clearAllCaches()
    {
        // Clear pattern-based caches
        Cache::flush(); // In production, you might want to use tags instead
    }
}