<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePageSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'hero_title',
        'hero_subtitle',
        'hero_image_path',
        'cta_text',
        'cta_link',
        'featured_places',
        'testimonials',
        'custom_scripts',
    ];

    protected $casts = [
        'featured_places' => 'array',
        'testimonials' => 'array',
    ];

    /**
     * Get the singleton instance.
     */
    public static function getInstance()
    {
        return static::firstOrCreate([]);
    }

    /**
     * Get the hero image URL.
     */
    public function getHeroImageUrlAttribute()
    {
        if (!$this->hero_image_path) {
            return null;
        }

        return asset('storage/' . $this->hero_image_path);
    }

    /**
     * Get the featured places.
     */
    public function getFeaturedPlaces()
    {
        if (empty($this->featured_places)) {
            return collect();
        }

        return Place::whereIn('id', $this->featured_places)
            ->published()
            ->get();
    }

    /**
     * Set the testimonials attribute.
     */
    public function setTestimonialsAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['testimonials'] = $value;
        } else {
            $this->attributes['testimonials'] = json_encode($value);
        }
    }
}