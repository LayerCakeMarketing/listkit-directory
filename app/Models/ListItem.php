<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Place;

class ListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_id',
        'type',
        'directory_entry_id',
        'title',
        'content',
        'data',
        'image',
        'item_image_cloudflare_id',
        'order_index',
        'affiliate_url',
        'notes',
        'custom_data',
    ];

    protected $casts = [
        'data' => 'array',
        'custom_data' => 'array',
        'order_index' => 'integer',
    ];

    protected $appends = ['display_title', 'display_content', 'item_image_url'];

    // Relationships
    public function list()
    {
        return $this->belongsTo(UserList::class, 'list_id');
    }

    public function place()
    {
        return $this->belongsTo(Place::class, 'directory_entry_id');
    }
    
    // Backward compatibility
    public function directoryEntry()
    {
        return $this->place();
    }

    // Accessors
    public function getDisplayTitleAttribute()
    {
        if ($this->type === 'directory_entry' && $this->place) {
            return $this->place->title;
        }
        return $this->title;
    }

    public function getItemImageUrlAttribute()
    {
        if ($this->item_image_cloudflare_id) {
            $imageService = app(\App\Services\CloudflareImageService::class);
            return $imageService->getImageUrl($this->item_image_cloudflare_id);
        }
        return $this->image; // Fallback to old image field
    }

    public function getDisplayContentAttribute()
    {
        if ($this->type === 'directory_entry' && $this->place) {
            return $this->place->description;
        }
        return $this->content;
    }

    public function getLocationDataAttribute()
    {
        if ($this->type === 'location' && $this->data) {
            return (object) $this->data;
        }
        if ($this->type === 'directory_entry' && $this->place && $this->place->location) {
            return (object) [
                'latitude' => $this->place->location->latitude,
                'longitude' => $this->place->location->longitude,
                'address' => $this->place->location->full_address,
            ];
        }
        return null;
    }

    // Helper methods
    public function getTypeIcon()
    {
        $icons = [
            'directory_entry' => 'folder',
            'text' => 'document-text',
            'location' => 'location-marker',
            'event' => 'calendar',
        ];
        
        return $icons[$this->type] ?? 'document';
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        // Add computed attributes
        $array['display_title'] = $this->display_title;
        $array['display_content'] = $this->display_content;
        
        // Include related data for directory entries
        if ($this->type === 'directory_entry' && $this->place) {
            $array['directory_entry'] = $this->place->toArray();
            $array['place'] = $this->place->toArray();
            if ($this->place->location) {
                $array['directory_entry']['location'] = $this->place->location->toArray();
                $array['place']['location'] = $this->place->location->toArray();
            }
        }
        
        return $array;
    }
}