<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'icon',
        'svg_icon',
        'cover_image_cloudflare_id',
        'cover_image_url',
        'quotes',
        'description',
        'order_index',
        'path',
        'depth',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'depth' => 'integer',
        'quotes' => 'array',
    ];

    protected $appends = ['cover_image_url_computed', 'quotes_count'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            
            // Handle duplicate slugs
            $count = static::where('slug', 'like', $category->slug . '%')->count();
            if ($count > 0) {
                $category->slug = $category->slug . '-' . ($count + 1);
            }
            
            // Auto-populate path and depth
            $category->updateHierarchyData();
        });

        static::updating(function ($category) {
            // Update path and depth if parent or slug changed
            if ($category->isDirty(['parent_id', 'slug'])) {
                $category->updateHierarchyData();
            }
        });

        static::updated(function ($category) {
            // Update all descendants' paths if this category's path changed
            if ($category->wasChanged(['path', 'slug'])) {
                $category->updateDescendantPaths();
            }
        });
    }

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order_index');
    }

    public function places()
    {
        return $this->hasMany(\App\Models\Place::class);
    }
    
    // Backward compatibility
    public function directoryEntries()
    {
        return $this->places();
    }
    
    // Curated lists relationship
    public function lists()
    {
        return $this->hasMany(\App\Models\CuratedList::class);
    }

    // Scopes
    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id')->orderBy('order_index');
    }

    public function scopeWithChildren($query)
    {
        return $query->with(['children' => function ($q) {
            $q->orderBy('order_index');
        }]);
    }

    // Helper methods
    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }

    public function getAncestorsAttribute()
    {
        $ancestors = collect([$this]);
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors;
    }

    // Get all descendant categories
    public function descendants()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }
        
        return $descendants;
    }

    // Get count of all entries in this category and its children
    public function getTotalEntriesCountAttribute()
    {
        $count = $this->directoryEntries()->count();
        
        foreach ($this->children as $child) {
            $count += $child->total_entries_count;
        }
        
        return $count;
    }

    // Get all places including from subcategories
    public function getAllPlaces()
    {
        $categoryIds = collect([$this->id]);
        
        // Add all descendant category IDs
        $descendants = $this->descendants();
        foreach ($descendants as $descendant) {
            $categoryIds->push($descendant->id);
        }
        
        return \App\Models\Place::whereIn('category_id', $categoryIds);
    }
    
    // Backward compatibility
    public function getAllDirectoryEntries()
    {
        return $this->getAllPlaces();
    }

    // Accessors
    public function getCoverImageUrlComputedAttribute()
    {
        if ($this->cover_image_cloudflare_id) {
            $imageService = app(\App\Services\CloudflareImageService::class);
            return $imageService->getImageUrl($this->cover_image_cloudflare_id);
        }
        return $this->cover_image_url; // Fallback to URL field
    }

    public function getQuotesCountAttribute()
    {
        return $this->quotes ? count($this->quotes) : 0;
    }

    // Get a random quote from the category
    public function getRandomQuote()
    {
        if (!$this->quotes || count($this->quotes) === 0) {
            return null;
        }
        
        return $this->quotes[array_rand($this->quotes)];
    }

    // Check if category has SVG icon
    public function hasSvgIcon()
    {
        return !empty($this->svg_icon);
    }

    // Get sanitized SVG icon (basic sanitization)
    public function getSanitizedSvgIcon()
    {
        if (!$this->svg_icon) {
            return null;
        }
        
        // Basic sanitization - remove script tags and event handlers
        $svg = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $this->svg_icon);
        $svg = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg);
        
        return $svg;
    }

    // ========================================
    // OPTIMIZED HIERARCHY METHODS FOR URL ROUTING
    // ========================================

    /**
     * Update path and depth based on parent relationship
     */
    public function updateHierarchyData()
    {
        if ($this->parent_id) {
            $parent = static::find($this->parent_id);
            if ($parent) {
                $this->path = $parent->path . '/' . $this->slug;
                $this->depth = $parent->depth + 1;
            }
        } else {
            $this->path = '/' . $this->slug;
            $this->depth = 0;
        }
    }

    /**
     * Update all descendant paths (called when parent path changes)
     */
    public function updateDescendantPaths()
    {
        $descendants = $this->children()->get();
        foreach ($descendants as $child) {
            $child->updateHierarchyData();
            $child->save();
            $child->updateDescendantPaths(); // Recursive
        }
    }

    /**
     * Scope: Get only parent (root) categories efficiently
     */
    public function scopeParentCategories($query)
    {
        return $query->whereNull('parent_id')
                    ->orderBy('order_index')
                    ->orderBy('name');
    }

    /**
     * Scope: Get categories by depth level
     */
    public function scopeByDepth($query, $depth)
    {
        return $query->where('depth', $depth)->orderBy('order_index');
    }

    /**
     * Scope: Get categories for URL routing (parent categories only)
     * This is optimized for the new URL structure: /places/{state}/{city}/{parent-category}/{place}
     */
    public function scopeForUrlRouting($query)
    {
        return $query->parentCategories()
                    ->select(['id', 'name', 'slug', 'path'])
                    ->with(['children' => function ($q) {
                        $q->select(['id', 'name', 'slug', 'parent_id', 'path'])
                          ->orderBy('order_index');
                    }]);
    }

    /**
     * Get parent category for URL routing
     * For child categories, return the parent. For parent categories, return self.
     */
    public function getUrlParentCategory()
    {
        if ($this->parent_id) {
            return $this->parent;
        }
        return $this;
    }

    /**
     * Efficiently find category by path (uses materialized path)
     */
    public static function findByPath($path)
    {
        // Normalize path (ensure leading slash, no trailing slash)
        $normalizedPath = '/' . trim($path, '/');
        
        return static::where('path', $normalizedPath)->first();
    }

    /**
     * Get all categories in a subtree (including self) using path prefix
     */
    public function getSubtreeCategories()
    {
        return static::where('path', 'LIKE', $this->path . '%')
                    ->orderBy('depth')
                    ->orderBy('order_index')
                    ->get();
    }

    /**
     * Optimized method to get all places in this category and subcategories
     * Uses a single query with IN clause instead of recursive PHP calls
     */
    public function getAllPlacesOptimized()
    {
        // Get all category IDs in this subtree using path prefix
        $categoryIds = static::where('path', 'LIKE', $this->path . '%')
                            ->pluck('id');

        return \App\Models\Place::whereIn('category_id', $categoryIds);
    }

    /**
     * Get breadcrumb trail for URLs using materialized path
     */
    public function getBreadcrumbs()
    {
        $pathSegments = array_filter(explode('/', $this->path));
        $breadcrumbs = collect();

        $currentPath = '';
        foreach ($pathSegments as $segment) {
            $currentPath .= '/' . $segment;
            $category = static::where('path', $currentPath)->first();
            if ($category) {
                $breadcrumbs->push($category);
            }
        }

        return $breadcrumbs;
    }

    /**
     * Check if this category is an ancestor of another category
     */
    public function isAncestorOf(Category $category)
    {
        return str_starts_with($category->path, $this->path . '/');
    }

    /**
     * Check if this category is a descendant of another category
     */
    public function isDescendantOf(Category $category)
    {
        return str_starts_with($this->path, $category->path . '/');
    }

    /**
     * Static method to rebuild all paths (for maintenance/repair)
     */
    public static function rebuildAllPaths()
    {
        // First, reset all paths and depths
        static::query()->update(['path' => null, 'depth' => null]);

        // Update root categories
        static::whereNull('parent_id')->get()->each(function ($category) {
            $category->path = '/' . $category->slug;
            $category->depth = 0;
            $category->save();
        });

        // Update child categories level by level
        for ($depth = 1; $depth <= 5; $depth++) {
            $updated = static::whereNull('path')
                           ->whereNotNull('parent_id')
                           ->whereHas('parent', function ($q) use ($depth) {
                               $q->where('depth', $depth - 1);
                           })
                           ->get()
                           ->each(function ($category) {
                               $category->updateHierarchyData();
                               $category->save();
                           });

            if ($updated->isEmpty()) {
                break;
            }
        }
    }
}