<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ListCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'svg_icon',
        'cover_image_cloudflare_id',
        'cover_image_url',
        'quotes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'quotes' => 'array',
    ];

    protected $appends = ['cover_image_url_computed', 'quotes_count'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
                
                // Handle duplicate slugs
                $count = static::where('slug', 'like', $category->slug . '%')->count();
                if ($count > 0) {
                    $category->slug = $category->slug . '-' . ($count + 1);
                }
            }
        });
    }

    // Relationships
    public function lists()
    {
        return $this->hasMany(UserList::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors & Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // Helper methods
    public function getListsCountAttribute()
    {
        return $this->lists()->count();
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
