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
    ];

    protected $casts = [
        'order_index' => 'integer',
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

    public function getPathAttribute()
    {
        $path = collect([$this]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent);
            $parent = $parent->parent;
        }
        
        return $path;
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
}