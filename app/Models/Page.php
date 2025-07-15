<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $table = 'pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = static::generateUniqueSlug($page->title);
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && $page->getOriginal('title') !== $page->title) {
                $page->slug = static::generateUniqueSlug($page->title, $page->id);
            }
        });
    }

    /**
     * Generate a unique slug.
     */
    protected static function generateUniqueSlug($title, $excludeId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)
            ->when($excludeId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Scope to get only published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get only draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Check if the page is published.
     */
    public function isPublished()
    {
        return $this->status === 'published';
    }

    /**
     * Get the page URL.
     */
    public function getUrlAttribute()
    {
        return '/' . $this->slug;
    }
}