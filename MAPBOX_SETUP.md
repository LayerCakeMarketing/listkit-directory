# Mapbox Integration Setup Guide

## Overview
The Mapbox integration has been implemented with the following features:
- Interactive map discovery page at `/places/map`
- GeoJSON API endpoints for map data
- Geospatial database columns and queries
- Vue 3 components using Mapbox GL JS
- Location-aware search and filtering

## Current Status

### ✅ Completed
1. **Database Setup**
   - Added geospatial columns to places table (latitude, longitude, address)
   - Created database indexes for performance
   - Added geospatial query scopes to Place model

2. **Backend API**
   - Created `/api/places/map-data` endpoint returning GeoJSON
   - Added location-based filtering and search
   - Implemented bounding box queries for viewport loading

3. **Frontend Components**
   - Created MapDiscovery.vue page component
   - Built PlacesMap.vue map component
   - Added useMapbox.js composable
   - Added usePlacesDiscovery.js composable
   - Added useGeolocation.js composable

4. **Configuration**
   - Mapbox public token configured in frontend/.env
   - Routes configured in frontend/src/router/index.js

### ⚠️ Manual Steps Required

Due to npm permission issues, you need to manually install the Mapbox dependencies:

```bash
# Fix npm permissions first (if needed)
sudo chown -R $(whoami) ~/.npm

# Install Mapbox dependencies
cd frontend
npm install mapbox-gl @types/mapbox-gl

# Start the development server
npm run dev
```

## Testing the Map View

1. **Start the servers**:
   ```bash
   # In one terminal
   php artisan serve

   # In another terminal
   cd frontend && npm run dev
   ```

2. **Access the map**:
   - Navigate to http://localhost:5173/places/map
   - The map should load with Mapbox tiles
   - Currently shows no markers (no places have coordinates yet)

3. **Test the API**:
   ```bash
   # Test map data endpoint
   curl "http://localhost:8000/api/places/map-data?bounds[north]=40&bounds[south]=30&bounds[east]=-70&bounds[west]=-80"

   # Should return GeoJSON format:
   {
     "type": "FeatureCollection",
     "features": [],
     "total": 0
   }
   ```

## Adding Test Data with Coordinates

To see places on the map, you need to add coordinates to existing places:

```bash
php artisan tinker
```

```php
// Add coordinates to a place
$place = \App\Models\Place::first();
if ($place) {
    $place->update([
        'latitude' => 37.7749,
        'longitude' => -122.4194,
        'address' => '123 Market St, San Francisco, CA 94103',
        'city' => 'San Francisco',
        'state' => 'CA'
    ]);
}

// Create a new place with coordinates
\App\Models\Place::create([
    'title' => 'Golden Gate Bridge',
    'slug' => 'golden-gate-bridge',
    'description' => 'Iconic suspension bridge',
    'type' => 'point_of_interest',
    'status' => 'published',
    'published_at' => now(),
    'latitude' => 37.8199,
    'longitude' => -122.4783,
    'address' => 'Golden Gate Bridge, San Francisco, CA',
    'city' => 'San Francisco',
    'state' => 'CA',
    'category_id' => 1 // Make sure this category exists
]);
```

## Next Steps

### 1. Geocoding Service (Priority: High)
Add a service to automatically geocode addresses:

```php
// app/Services/MapboxGeocodingService.php
namespace App\Services;

class MapboxGeocodingService
{
    protected $token;
    
    public function __construct()
    {
        $this->token = config('services.mapbox.secret_token');
    }
    
    public function geocodeAddress(string $address): ?array
    {
        // Implementation using Mapbox Geocoding API
    }
}
```

### 2. Place Clustering (Priority: Medium)
The frontend is ready for clustering. Just need to:
- Ensure places have coordinates
- The map component will automatically cluster when >50 places in view

### 3. Location-Based Search (Priority: Medium)
Enhance search with location context:
- Add distance calculations to search results
- Sort by proximity when user location is available
- Add "Near me" quick filter

### 4. Import Existing Places
Update existing places with coordinates:
```bash
php artisan make:command GeocodeExistingPlaces
```

This command should:
- Find places without coordinates
- Geocode their addresses using Mapbox
- Update the database with lat/lng

## Troubleshooting

### Map not showing
1. Check browser console for errors
2. Verify Mapbox token is correct in frontend/.env
3. Ensure npm dependencies are installed

### No places on map
1. Verify places have coordinates in database
2. Check that places are published (`status = 'published'`)
3. Test the API endpoint directly

### Performance issues
1. Ensure database indexes exist
2. Limit viewport queries to reasonable bounds
3. Enable clustering for >50 markers

## Architecture Notes

### Database
- Uses PostgreSQL's built-in numeric types for coordinates
- Indexes on (latitude, longitude) for fast spatial queries
- Ready for PostGIS upgrade when needed

### API
- Returns standard GeoJSON format
- Supports bounding box queries
- Includes place metadata in feature properties

### Frontend
- Lazy loads Mapbox GL JS
- Implements viewport-based loading
- Supports multiple view modes (split/map/list)
- Mobile responsive with bottom sheet

## Resources
- [Mapbox GL JS Documentation](https://docs.mapbox.com/mapbox-gl-js/)
- [GeoJSON Specification](https://geojson.org/)
- [Vue 3 + Mapbox Examples](https://docs.mapbox.com/help/tutorials/use-mapbox-gl-js-with-vue/)