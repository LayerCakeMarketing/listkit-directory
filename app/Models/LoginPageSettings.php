<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginPageSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'background_image_path',
        'background_image_id',
        'welcome_message',
        'custom_css',
        'social_login_enabled',
    ];

    protected $casts = [
        'social_login_enabled' => 'boolean',
    ];
    
    protected $appends = ['background_image_url'];

    /**
     * Get the singleton instance.
     */
    public static function getInstance()
    {
        return static::firstOrCreate([]);
    }

    /**
     * Get the background image URL.
     */
    public function getBackgroundImageUrlAttribute()
    {
        // Prioritize Cloudflare image
        if ($this->background_image_id) {
            return "https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/{$this->background_image_id}/lgformat";
        }
        
        // Fall back to local storage
        if ($this->background_image_path) {
            return asset('storage/' . $this->background_image_path);
        }

        return null;
    }
}