<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocalPageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'page_type',
        'title',
        'intro_text',
        'meta_description',
        'featured_lists',
        'featured_places',
        'content_sections',
        'settings',
        'is_active'
    ];

    protected $casts = [
        'featured_lists' => 'array',
        'featured_places' => 'array',
        'content_sections' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the region that owns the page settings.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get featured lists with full models
     */
    public function getFeaturedListsAttribute($value)
    {
        if (!$value) return collect();
        
        $listData = is_array($value) ? $value : json_decode($value, true);
        if (!$listData) return collect();
        
        $listIds = collect($listData)->pluck('id')->filter();
        $lists = UserList::whereIn('id', $listIds)
            ->with(['user', 'items.directoryEntry'])
            ->get()
            ->keyBy('id');
        
        return collect($listData)->map(function ($item) use ($lists) {
            $list = $lists->get($item['id']);
            if ($list) {
                $list->pivot = (object) ['order' => $item['order'] ?? 0];
            }
            return $list;
        })->filter()->sortBy('pivot.order');
    }

    /**
     * Get featured places with full models
     */
    public function getFeaturedPlacesAttribute($value)
    {
        if (!$value) return collect();
        
        $placeData = is_array($value) ? $value : json_decode($value, true);
        if (!$placeData) return collect();
        
        $placeIds = collect($placeData)->pluck('id')->filter();
        $places = Place::whereIn('id', $placeIds)
            ->with(['category', 'location', 'stateRegion', 'cityRegion'])
            ->get()
            ->keyBy('id');
        
        return collect($placeData)->map(function ($item) use ($places) {
            $place = $places->get($item['id']);
            if ($place) {
                $place->pivot = (object) [
                    'order' => $item['order'] ?? 0,
                    'tagline' => $item['tagline'] ?? null
                ];
            }
            return $place;
        })->filter()->sortBy('pivot.order');
    }

    /**
     * Get settings for a specific page type and region
     */
    public static function getForPage($pageType = 'index', $regionId = null)
    {
        $query = static::where('page_type', $pageType)
            ->where('is_active', true);
        
        if ($regionId) {
            $query->where('region_id', $regionId);
        } else {
            $query->whereNull('region_id');
        }
        
        return $query->first();
    }

    /**
     * Set featured lists
     */
    public function setFeaturedListsData($lists)
    {
        $this->attributes['featured_lists'] = json_encode(
            collect($lists)->map(function ($item, $index) {
                return [
                    'id' => is_array($item) ? $item['id'] : $item,
                    'order' => is_array($item) ? ($item['order'] ?? $index) : $index
                ];
            })->values()->toArray()
        );
    }

    /**
     * Set featured places
     */
    public function setFeaturedPlacesData($places)
    {
        $this->attributes['featured_places'] = json_encode(
            collect($places)->map(function ($item, $index) {
                return [
                    'id' => is_array($item) ? $item['id'] : $item,
                    'order' => is_array($item) ? ($item['order'] ?? $index) : $index,
                    'tagline' => is_array($item) ? ($item['tagline'] ?? null) : null
                ];
            })->values()->toArray()
        );
    }
}
