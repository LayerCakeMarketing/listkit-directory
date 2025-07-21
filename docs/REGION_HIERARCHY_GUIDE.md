# Region Hierarchy Guide

## Overview
The region system uses a 3-level hierarchy:

### Level 1 - States
- Examples: California, Florida, New York, Texas
- Type: "state"
- No parent region
- URL pattern: `/california`

### Level 2 - Cities
- Examples: Los Angeles, Miami, New York City, Austin
- Type: "city"
- Must have a parent state
- URL pattern: `/california/los-angeles`

### Level 3 - Neighborhoods
- Examples: Hollywood, Downtown, Beverly Hills, Venice Beach
- Type: "neighborhood"
- Must have a parent city
- URL pattern: `/california/los-angeles/hollywood`

## Adding Regions in Admin

### To Add a State (Level 1):
1. Click "Create Region" → "Add State (Level 1)"
2. Enter the state name (e.g., "California")
3. Type is automatically set to "state"
4. No parent selection needed
5. Optionally add polygon boundaries (see below)

### To Add a City (Level 2):
1. Click "Create Region" → "Add City (Level 2)"
2. Enter the city name (e.g., "Los Angeles")
3. Select the parent state (e.g., "California")
4. Type is automatically set to "city"
5. Optionally add polygon boundaries

### To Add a Neighborhood (Level 3):
1. Click "Create Region" → "Add Neighborhood (Level 3)"
2. Enter the neighborhood name (e.g., "Hollywood")
3. Select the parent city (e.g., "Los Angeles")
4. Type is automatically set to "neighborhood"
5. Optionally add polygon boundaries

## Polygon Boundaries (Optional)

Boundaries are stored as PostGIS polygons and enable:
- Automatic assignment of places to regions based on coordinates
- Map visualizations
- Spatial queries (find all places within a region)

### Adding Boundaries:

1. **Manual GeoJSON**: You can paste GeoJSON polygon data
   ```json
   {
     "type": "Polygon",
     "coordinates": [[
       [-118.2437, 34.0522],
       [-118.2437, 34.0622],
       [-118.2337, 34.0622],
       [-118.2337, 34.0522],
       [-118.2437, 34.0522]
     ]]
   }
   ```

2. **External Sources**: You can get boundary data from:
   - [geojson.io](http://geojson.io) - Draw boundaries manually
   - [OpenStreetMap](https://www.openstreetmap.org) - Export existing boundaries
   - [US Census Bureau](https://www.census.gov/geographies/mapping-files.html) - Official boundaries
   - Google Maps APIs

3. **Import Tools**: For bulk imports, you can:
   - Use shapefile converters
   - Import from KML/KMZ files
   - Use geocoding services

## How Places Are Assigned to Regions

When a place has coordinates (latitude/longitude), the system:
1. Checks if the point falls within any neighborhood polygon
2. If yes, assigns that neighborhood and its parent city/state
3. If no polygon match, falls back to address matching
4. Updates the place's `state_region_id`, `city_region_id`, and `neighborhood_region_id`

## Example Workflow

To set up regions for Los Angeles:

1. **Create State**: California (Level 1)
2. **Create City**: Los Angeles (Level 2, parent: California)
3. **Create Neighborhoods**: 
   - Hollywood (Level 3, parent: Los Angeles)
   - Beverly Hills (Level 3, parent: Los Angeles)
   - Venice Beach (Level 3, parent: Los Angeles)
   - Downtown LA (Level 3, parent: Los Angeles)

Each region can then have:
- Custom landing page with cover image and description
- Featured businesses
- Statistics (population, median income, etc.)
- SEO metadata

## Running Region Assignment

After creating regions, assign existing places:
```bash
# Assign all unassigned places
php artisan regions:assign-entries

# Assign only places in California
php artisan regions:assign-entries --state="California"

# Force reassignment of all places
php artisan regions:assign-entries --force
```