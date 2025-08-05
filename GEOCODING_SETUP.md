# Geocoding Setup Guide

## Overview
This guide covers the geocoding implementation that automatically validates addresses and adds coordinates to places for the map view.

## Configuration

### 1. Add Mapbox Secret Token to .env
```bash
# Add this to your .env file
MAPBOX_SECRET_TOKEN=sk.eyJ1IjoiaWxsdW1pbmF0ZWxvY2FsIiwiYSI6ImNtZHRocHJhZzEzc3EybHB0MmFkbTkxeDIifQ.4dlQL99S6lPT4VcIkp5fBg
```

### 2. Clear Config Cache
```bash
php artisan config:clear
```

## Features Implemented

### 1. **Automatic Geocoding**
- Places are automatically geocoded when created or when address changes
- Uses Mapbox Geocoding API with caching
- Confidence scoring to ensure accuracy
- Observer pattern for seamless integration

### 2. **Address Validation Component**
- Vue component for address input with real-time validation
- Visual feedback on address validity
- Interactive map preview
- Draggable marker for manual adjustments

### 3. **API Endpoints**
```
POST /api/geocoding/validate  - Validate address components
POST /api/geocoding/geocode   - Geocode an address string
POST /api/geocoding/reverse   - Get address from coordinates
```

### 4. **Batch Geocoding Command**
```bash
# Geocode all places missing coordinates
php artisan places:geocode

# Options:
php artisan places:geocode --limit=50        # Process only 50 places
php artisan places:geocode --dry-run         # Test without saving
php artisan places:geocode --missing-only    # Only places without coords
php artisan places:geocode --force           # Re-geocode all places
```

## Integration in Create/Edit Forms

### Using the AddressInput Component

```vue
<template>
  <form @submit.prevent="savePlace">
    <!-- Other fields -->
    
    <!-- Address Input with Geocoding -->
    <AddressInput
      v-model="form"
      :errors="errors"
      :auto-geocode="true"
      :show-map-preview="true"
      @validation-complete="handleValidation"
    />
    
    <!-- Submit button -->
    <button type="submit">Save Place</button>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import AddressInput from '@/components/AddressInput.vue'

const form = ref({
  address: '',
  city: '',
  state: '',
  latitude: null,
  longitude: null
})

const errors = ref({})

const handleValidation = (result) => {
  console.log('Address validation result:', result)
  // Handle validation feedback
}

const savePlace = async () => {
  // Form will include geocoded coordinates
  await axios.post('/api/places', form.value)
}
</script>
```

## How It Works

### 1. **Manual Address Entry**
- User enters address components
- Auto-validation after typing stops (debounced)
- Visual feedback on address validity
- Automatic coordinate assignment

### 2. **Geocoding Button**
- Click location icon to manually trigger geocoding
- Shows loading state during API call
- Updates coordinates and shows confidence

### 3. **Map Preview**
- Shows marker at geocoded location
- Draggable marker for manual adjustments
- Updates coordinates when marker is moved

### 4. **Backend Processing**
- PlaceObserver watches for address changes
- Automatically geocodes if needed
- Caches results to avoid duplicate API calls
- Respects rate limits

## Best Practices

### 1. **Address Format**
For best results, use complete addresses:
```
123 Main Street, San Francisco, CA 94105
```

### 2. **Validation Thresholds**
- **High Confidence (≥0.8)**: Address is valid and accurate
- **Medium Confidence (0.5-0.8)**: Address found but may need verification
- **Low Confidence (<0.5)**: Address should be manually verified

### 3. **Manual Override**
Users can always manually enter coordinates if geocoding fails or is inaccurate.

## Troubleshooting

### Common Issues

1. **"Address not found"**
   - Ensure address is complete
   - Try adding ZIP code
   - Check for typos

2. **Wrong location**
   - Use the map preview to verify
   - Drag marker to correct position
   - Coordinates update automatically

3. **API Errors**
   - Check MAPBOX_SECRET_TOKEN is set
   - Verify API limits not exceeded
   - Check Laravel logs for details

### Testing Geocoding

```bash
# Test in tinker
php artisan tinker

$service = app(App\Services\GeocodingService::class);
$result = $service->geocodeAddress('Golden Gate Bridge, San Francisco, CA');
print_r($result);
```

## Next Steps

1. **Add to existing forms**: Integrate AddressInput component in place create/edit forms
2. **Geocode existing data**: Run `php artisan places:geocode` to add coordinates to existing places
3. **Monitor usage**: Track API usage in Mapbox dashboard
4. **Enhance accuracy**: Add business name to address for POI matching

## API Limits

- Mapbox Free Tier: 100,000 requests/month
- Rate Limit: 600 requests/minute
- Caching reduces API calls significantly

## Security Notes

- Secret token is only used server-side
- Public token (pk.) used in frontend
- All requests are validated and sanitized
- Results are cached to prevent abuse