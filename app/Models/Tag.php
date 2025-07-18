<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'color',
        'description',
        'is_featured',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_system' => 'boolean',
        'usage_count' => 'integer',
        'places_count' => 'integer',
        'lists_count' => 'integer',
        'posts_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
                
                // Handle duplicate slugs
                $count = static::where('slug', 'like', $tag->slug . '%')->count();
                if ($count > 0) {
                    $tag->slug = $tag->slug . '-' . ($count + 1);
                }
            }
        });

        static::deleting(function ($tag) {
            // Prevent deletion of system tags
            if ($tag->is_system) {
                return false;
            }
        });
    }

    // Relationships
    public function taggables()
    {
        return $this->hasMany(Taggable::class);
    }

    public function places()
    {
        return $this->morphedByMany(Place::class, 'taggable');
    }

    public function lists()
    {
        return $this->morphedByMany(UserList::class, 'taggable');
    }

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
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
    public function updateCounts()
    {
        $this->places_count = $this->places()->count();
        $this->lists_count = $this->lists()->count();
        $this->posts_count = $this->posts()->count();
        $this->usage_count = $this->places_count + $this->lists_count + $this->posts_count;
        $this->saveQuietly();
    }

    public function getSuggestedColor()
    {
        $colors = [
            'general' => '#6B7280', // gray
            'category' => '#3B82F6', // blue
            'location' => '#10B981', // green
            'event' => '#F59E0B', // amber
            'trending' => '#EF4444', // red
        ];

        return $colors[$this->type] ?? $colors['general'];
    }

    // Static methods
    public static function findOrCreateByName($name, $userId = null)
    {
        $slug = Str::slug($name);
        
        $tag = static::where('slug', $slug)->first();
        
        if (!$tag) {
            $tag = static::create([
                'name' => $name,
                'slug' => $slug,
                'created_by' => $userId ?: auth()->id(),
            ]);
        }
        
        return $tag;
    }
}
