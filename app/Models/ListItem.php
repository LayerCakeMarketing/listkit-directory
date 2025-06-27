<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // Relationships
    public function list()
    {
        return $this->belongsTo(UserList::class, 'list_id');
    }

    public function directoryEntry()
    {
        return $this->belongsTo(DirectoryEntry::class);
    }

    // Accessors
    public function getDisplayTitleAttribute()
    {
        if ($this->type === 'directory_entry' && $this->directoryEntry) {
            return $this->directoryEntry->title;
        }
        return $this->title;
    }

    public function getDisplayContentAttribute()
    {
        if ($this->type === 'directory_entry' && $this->directoryEntry) {
            return $this->directoryEntry->description;
        }
        return $this->content;
    }

    public function getLocationDataAttribute()
    {
        if ($this->type === 'location' && $this->data) {
            return (object) $this->data;
        }
        if ($this->type === 'directory_entry' && $this->directoryEntry && $this->directoryEntry->location) {
            return (object) [
                'latitude' => $this->directoryEntry->location->latitude,
                'longitude' => $this->directoryEntry->location->longitude,
                'address' => $this->directoryEntry->location->full_address,
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
        if ($this->type === 'directory_entry' && $this->directoryEntry) {
            $array['directory_entry'] = $this->directoryEntry->toArray();
            if ($this->directoryEntry->location) {
                $array['directory_entry']['location'] = $this->directoryEntry->location->toArray();
            }
        }
        
        return $array;
    }
}