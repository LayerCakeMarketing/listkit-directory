<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CuratedList extends Model
{
    use HasFactory;

    protected $table = 'lists';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_region_specific',
        'region_id',
        'is_category_specific',
        'category_id',
        'place_ids',
        'cover_image_url',
        'icon',
        'order_index',
        'is_featured',
        'is_published',
        'meta_title',
        'meta_description',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'place_ids' => 'array',
        'is_region_specific' => 'boolean',
        'is_category_specific' => 'boolean',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($list) {
            if (empty($list->slug)) {
                $list->slug = Str::slug($list->title);
                
                // Ensure unique slug
                $count = static::where('slug', 'like', $list->slug . '%')->count();
                if ($count > 0) {
                    $list->slug = $list->slug . '-' . ($count + 1);
                }
            }

            if (auth()->check()) {
                $list->created_by_user_id = auth()->id();
            }
        });

        static::updating(function ($list) {
            if (auth()->check()) {
                $list->updated_by_user_id = auth()->id();
            }
        });
    }

    // Relationships
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function places()
    {
        return $this->belongsToMany(Place::class, 'place_ids')
            ->using(PlaceListPivot::class)
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForRegion($query, $regionId)
    {
        return $query->where('is_region_specific', true)
            ->where('region_id', $regionId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('is_category_specific', true)
            ->where('category_id', $categoryId);
    }

    public function scopeGeneral($query)
    {
        return $query->where('is_region_specific', false)
            ->where('is_category_specific', false);
    }

    // Methods
    public function getPlacesAttribute()
    {
        if (empty($this->place_ids)) {
            return collect();
        }

        return Place::whereIn('id', $this->place_ids)
            ->orderByRaw('ARRAY_POSITION(ARRAY[' . implode(',', $this->place_ids) . '], id)')
            ->get();
    }

    public function addPlace($placeId)
    {
        $placeIds = $this->place_ids ?? [];
        if (!in_array($placeId, $placeIds)) {
            $placeIds[] = $placeId;
            $this->place_ids = $placeIds;
            $this->save();
        }
    }

    public function removePlace($placeId)
    {
        $placeIds = $this->place_ids ?? [];
        $placeIds = array_values(array_diff($placeIds, [$placeId]));
        $this->place_ids = $placeIds;
        $this->save();
    }

    public function reorderPlaces(array $orderedPlaceIds)
    {
        $this->place_ids = $orderedPlaceIds;
        $this->save();
    }

    public function getUrlAttribute()
    {
        if ($this->is_region_specific && $this->region) {
            return route('lists.show', ['region' => $this->region->slug, 'list' => $this->slug]);
        } elseif ($this->is_category_specific && $this->category) {
            return route('lists.category', ['category' => $this->category->slug, 'list' => $this->slug]);
        }
        
        return route('lists.general', ['list' => $this->slug]);
    }
}