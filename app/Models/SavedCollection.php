<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SavedCollection extends Model
{
    protected $fillable = [
        'user_id',
        'name', 
        'slug',
        'description',
        'color',
        'icon',
        'order_index',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'order_index' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($collection) {
            if (empty($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
                
                // Handle duplicate slugs for the same user
                $count = static::where('slug', 'like', $collection->slug . '%')
                              ->where('user_id', $collection->user_id)
                              ->count();
                if ($count > 0) {
                    $collection->slug = $collection->slug . '-' . ($count + 1);
                }
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savedItems(): HasMany
    {
        return $this->hasMany(SavedItem::class, 'collection_id');
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index')->orderBy('created_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function itemsCount()
    {
        return $this->savedItems()->count();
    }

    public function canEdit($user = null)
    {
        $user = $user ?? auth()->user();
        return $user && $user->id === $this->user_id;
    }

    // Available colors for collections
    public static function availableColors()
    {
        return [
            'gray' => 'Gray',
            'red' => 'Red',
            'orange' => 'Orange', 
            'yellow' => 'Yellow',
            'green' => 'Green',
            'teal' => 'Teal',
            'blue' => 'Blue',
            'indigo' => 'Indigo',
            'purple' => 'Purple',
            'pink' => 'Pink'
        ];
    }

    // Available icons
    public static function availableIcons()
    {
        return [
            'folder' => 'Folder',
            'star' => 'Star',
            'heart' => 'Heart',
            'bookmark' => 'Bookmark',
            'flag' => 'Flag',
            'tag' => 'Tag',
            'map' => 'Map',
            'camera' => 'Camera',
            'shopping-bag' => 'Shopping Bag',
            'briefcase' => 'Briefcase'
        ];
    }
}
