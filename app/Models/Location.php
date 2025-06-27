<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'directory_entry_id', 'address_line1', 'address_line2', 'city',
        'state', 'zip_code', 'country', 'latitude', 'longitude',
        'hours_of_operation', 'holiday_hours', 'is_wheelchair_accessible',
        'has_parking', 'amenities', 'place_id'
    ];

    protected $casts = [
        'hours_of_operation' => 'array',
        'holiday_hours' => 'array',
        'amenities' => 'array',
        'is_wheelchair_accessible' => 'boolean',
        'has_parking' => 'boolean',
    ];

    // Relationships
    public function directoryEntry()
    {
        return $this->belongsTo(DirectoryEntry::class);
    }

    // Scopes for geospatial queries
    public function scopeWithinRadius($query, $lat, $lng, $radiusInMiles = 25)
    {
        $radiusInMeters = $radiusInMiles * 1609.34;
        
        if (config('database.default') === 'pgsql') {
            return $query->whereRaw(
                'ST_DWithin(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$lng, $lat, $radiusInMeters]
            )->addSelect(\DB::raw("ST_Distance(geom, ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography) as distance"));
        } else {
            return $query->whereRaw(
                'ST_Distance_Sphere(coordinates, POINT(?, ?)) <= ?',
                [$lng, $lat, $radiusInMeters]
            )->addSelect(\DB::raw("ST_Distance_Sphere(coordinates, POINT({$lng}, {$lat})) as distance"));
        }
    }

    public function scopeInCity($query, $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function scopeInState($query, $state)
    {
        return $query->where('state', $state);
    }

    // Helper methods
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->zip_code
        ]);

        return implode(', ', $parts);
    }

    public function isOpenNow()
    {
        if (!$this->hours_of_operation) return null;

        $dayOfWeek = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i');

        if (!isset($this->hours_of_operation[$dayOfWeek])) return false;

        $hours = $this->hours_of_operation[$dayOfWeek];
        if ($hours === 'closed') return false;

        // Parse hours like "09:00-17:00"
        if (preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $hours, $matches)) {
            $openTime = $matches[1];
            $closeTime = $matches[2];
            
            return $currentTime >= $openTime && $currentTime <= $closeTime;
        }

        return null;
    }
}