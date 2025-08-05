<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_key',
        'heading',
        'paragraph',
        'cover_image_id',
        'cover_image_url',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
    
    protected $appends = ['image_url'];

    /**
     * Get the image URL (prioritizing Cloudflare).
     */
    public function getImageUrlAttribute()
    {
        // Prioritize Cloudflare image
        if ($this->cover_image_id) {
            return "https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/{$this->cover_image_id}/lgformat";
        }
        
        // Fall back to URL
        if ($this->cover_image_url) {
            return $this->cover_image_url;
        }

        return null;
    }

    /**
     * Get a marketing page by key.
     */
    public static function getByKey(string $key)
    {
        return static::where('page_key', $key)->firstOrCreate([
            'page_key' => $key
        ], [
            'heading' => '',
            'paragraph' => '',
        ]);
    }
}