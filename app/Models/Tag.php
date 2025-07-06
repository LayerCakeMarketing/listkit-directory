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
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
    }

    // Relationships
    public function taggables()
    {
        return $this->hasMany(Taggable::class);
    }

    public function lists()
    {
        return $this->morphedByMany(UserList::class, 'taggable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('taggables')
                    ->orderBy('taggables_count', 'desc')
                    ->limit($limit);
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
    public function getUsageCountAttribute()
    {
        return $this->taggables()->count();
    }

    public function getUsageByTypeAttribute()
    {
        return $this->taggables()
                   ->select('taggable_type')
                   ->selectRaw('count(*) as count')
                   ->groupBy('taggable_type')
                   ->pluck('count', 'taggable_type');
    }

    // Static methods
    public static function findOrCreateByName($name)
    {
        $slug = Str::slug($name);
        
        return static::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
            ]
        );
    }
}
