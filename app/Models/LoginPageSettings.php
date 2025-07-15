<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginPageSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'background_image_path',
        'welcome_message',
        'custom_css',
        'social_login_enabled',
    ];

    protected $casts = [
        'social_login_enabled' => 'boolean',
    ];

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
        if (!$this->background_image_path) {
            return null;
        }

        return asset('storage/' . $this->background_image_path);
    }
}