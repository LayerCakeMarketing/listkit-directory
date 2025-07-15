<?php

namespace App\Services;

use App\Models\Region;
use App\Models\User;
use App\Models\Category;
use App\Models\Place;
use Illuminate\Support\Facades\Cache;

class PathResolver
{
    /**
     * Resolve a path to its corresponding model and type
     *
     * @param string $path
     * @return array|null
     */
    public function resolve(string $path): ?array
    {
        $segments = explode('/', trim($path, '/'));
        $count = count($segments);
        
        // Cache the resolution for performance
        $cacheKey = 'path_resolution:' . md5($path);
        
        return Cache::remember($cacheKey, 3600, function() use ($segments, $count) {
            // Try to resolve as region (1-3 segments)
            if ($count >= 1 && $count <= 3) {
                if ($region = $this->resolveRegion($segments)) {
                    return [
                        'type' => 'region',
                        'model' => $region,
                        'data' => $region->toFrontend()
                    ];
                }
            }
            
            // Try to resolve as place (3 segments: category/subcategory/entry)
            if ($count === 3) {
                if ($place = $this->resolvePlace($segments)) {
                    return [
                        'type' => 'place',
                        'model' => $place,
                        'data' => $place
                    ];
                }
            }
            
            // Try to resolve as user (1 segment) or user list (2 segments)
            if ($count <= 2) {
                if ($resolution = $this->resolveUserPath($segments)) {
                    return $resolution;
                }
            }
            
            return null;
        });
    }
    
    /**
     * Resolve a region by its path segments
     *
     * @param array $segments
     * @return Region|null
     */
    private function resolveRegion(array $segments): ?Region
    {
        $region = null;
        $parent = null;
        
        foreach ($segments as $slug) {
            $query = Region::where('slug', $slug);
            
            if ($parent) {
                $query->where('parent_id', $parent->id);
            } else {
                $query->whereNull('parent_id');
            }
            
            $region = $query->first();
            
            if (!$region) {
                return null;
            }
            
            $parent = $region;
        }
        
        return $region;
    }
    
    /**
     * Resolve a place by category path
     *
     * @param array $segments
     * @return Place|null
     */
    private function resolvePlace(array $segments): ?Place
    {
        if (count($segments) !== 3) {
            return null;
        }
        
        [$parentSlug, $childSlug, $entrySlug] = $segments;
        
        // Find parent category
        $parentCategory = Category::where('slug', $parentSlug)
            ->whereNull('parent_id')
            ->first();
            
        if (!$parentCategory) {
            return null;
        }
        
        // Find child category
        $childCategory = Category::where('slug', $childSlug)
            ->where('parent_id', $parentCategory->id)
            ->first();
            
        if (!$childCategory) {
            return null;
        }
        
        // Find entry
        return Place::where('slug', $entrySlug)
            ->where('category_id', $childCategory->id)
            ->where('status', 'published')
            ->with(['category.parent', 'location'])
            ->first();
    }
    
    /**
     * Resolve a user or user list path
     *
     * @param array $segments
     * @return array|null
     */
    private function resolveUserPath(array $segments): ?array
    {
        $userIdentifier = $segments[0];
        
        // Find user by custom URL or username
        $user = User::where('custom_url', $userIdentifier)
            ->orWhere('username', $userIdentifier)
            ->first();
            
        if (!$user) {
            return null;
        }
        
        // Single segment = user profile
        if (count($segments) === 1) {
            return [
                'type' => 'user',
                'model' => $user,
                'data' => $user
            ];
        }
        
        // Two segments = user list
        if (count($segments) === 2) {
            $listSlug = $segments[1];
            $list = $user->lists()
                ->where('slug', $listSlug)
                ->first();
                
            if (!$list) {
                return null;
            }
            
            return [
                'type' => 'list',
                'model' => $list,
                'data' => [
                    'user' => $user,
                    'list' => $list
                ]
            ];
        }
        
        return null;
    }
    
    /**
     * Clear cache for a specific path
     *
     * @param string $path
     * @return void
     */
    public function clearCache(string $path): void
    {
        $cacheKey = 'path_resolution:' . md5($path);
        Cache::forget($cacheKey);
    }
    
    /**
     * Clear all path resolution cache
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        // In production, you might want to use cache tags
        Cache::flush();
    }
}