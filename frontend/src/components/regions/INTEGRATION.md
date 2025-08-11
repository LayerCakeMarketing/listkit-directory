# RegionTwoColumnView Integration Guide

This guide shows how to integrate the `RegionTwoColumnView` component into your existing Laravel + Vue application.

## Quick Start

1. **Visit the Demo**: Navigate to `/demo/regions` to see the component in action
2. **Choose a Region**: Select from the sample regions to explore features
3. **Test Interactions**: Try search, filtering, and place selection

## Backend API Setup

### Required Endpoints

Create these API endpoints in your Laravel application:

#### 1. Region Two-Column Data
```php
// routes/api.php
Route::get('/region-map/{region}/two-column', [RegionController::class, 'twoColumnView']);
```

```php
// app/Http/Controllers/RegionController.php
public function twoColumnView(Region $region)
{
    $places = $region->places()
        ->with(['category'])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->where('status', 'active')
        ->paginate(20);

    return response()->json([
        'region' => [
            'id' => $region->id,
            'name' => $region->name,
            'type' => $region->type,
            'slug' => $region->slug,
            'description' => $region->description,
            'place_count' => $region->places()->count(),
            'population' => $region->population,
            'bounds' => $region->bounds ? [
                'north' => $region->bounds['north'],
                'south' => $region->bounds['south'],
                'east' => $region->bounds['east'],
                'west' => $region->bounds['west'],
            ] : null,
            'park_info' => $region->type === 'national_park' || $region->type === 'state_park' ? [
                'hours' => $region->hours,
                'entrance_fee' => $region->entrance_fee,
                'activities' => $region->activities ?? [],
            ] : null,
        ],
        'places' => $places->items(),
        'has_next_page' => $places->hasMorePages(),
        'current_page' => $places->currentPage(),
    ]);
}
```

#### 2. Places with Filtering
```php
// routes/api.php
Route::get('/region-map/{region}/places', [RegionController::class, 'regionPlaces']);
```

```php
// app/Http/Controllers/RegionController.php
public function regionPlaces(Request $request, Region $region)
{
    $query = $region->places()
        ->with(['category'])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->where('status', 'active');

    // Search filter
    if ($request->has('search')) {
        $search = $request->get('search');
        $query->where(function($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%")
              ->orWhere('address', 'ILIKE', "%{$search}%")
              ->orWhereHas('category', function($cat) use ($search) {
                  $cat->where('name', 'ILIKE', "%{$search}%");
              });
        });
    }

    // Category filter
    if ($request->has('category')) {
        $query->where('category_id', $request->get('category'));
    }

    $places = $query->paginate(20);

    return response()->json([
        'places' => $places->items(),
        'has_next_page' => $places->hasMorePages(),
        'current_page' => $places->currentPage(),
    ]);
}
```

### Database Schema Updates

Ensure your regions table has these columns:

```php
// Migration: add_two_column_fields_to_regions_table.php
Schema::table('regions', function (Blueprint $table) {
    $table->json('bounds')->nullable()->after('description');
    $table->string('hours')->nullable()->after('bounds'); // For parks
    $table->string('entrance_fee')->nullable()->after('hours'); // For parks
    $table->json('activities')->nullable()->after('entrance_fee'); // For parks
    $table->integer('population')->nullable()->after('activities');
});
```

## Frontend Integration

### 1. Using in Existing Views

Replace existing region views with the two-column layout:

```vue
<!-- views/regions/Show.vue -->
<template>
  <div class="h-screen">
    <!-- Header with breadcrumbs -->
    <header class="bg-white shadow-sm px-6 py-4">
      <nav class="text-sm text-gray-500 mb-2">
        <router-link to="/">Home</router-link>
        <span class="mx-2">/</span>
        <router-link 
          v-if="region?.parent" 
          :to="region.parent.url"
        >
          {{ region.parent.name }}
        </router-link>
        <span v-if="region?.parent" class="mx-2">/</span>
        <span class="text-gray-900 font-medium">{{ region?.name }}</span>
      </nav>
      <h1 class="text-2xl font-bold">{{ region?.name }}</h1>
    </header>
    
    <!-- Two-column region view -->
    <main class="h-[calc(100vh-80px)]">
      <RegionTwoColumnView
        :region-id="regionId"
        @place-selected="handlePlaceSelection"
        @region-loaded="region = $event"
      />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import RegionTwoColumnView from '@/components/regions/RegionTwoColumnView.vue'

const route = useRoute()
const region = ref(null)

const regionId = computed(() => 
  parseInt(route.params.regionId) || parseInt(route.params.id)
)

const handlePlaceSelection = (place) => {
  // Could navigate to place detail page
  if (place) {
    console.log('Selected place:', place)
    // router.push(`/places/${place.slug}`)
  }
}
</script>
```

### 2. Route Integration

Update your routes to use the new component:

```javascript
// router/index.js
{
  path: '/regions/:regionId',
  name: 'RegionView',
  component: () => import('@/views/regions/Show.vue'),
  props: route => ({ regionId: parseInt(route.params.regionId) })
},
{
  path: '/local/:state',
  name: 'State',
  component: () => import('@/views/regions/Show.vue'),
  props: route => ({ 
    regionId: route.meta.regionId // Set via route meta or API lookup
  })
},
```

### 3. Navigation Menu Integration

Add the component to your navigation:

```vue
<!-- components/NavigationMenu.vue -->
<nav>
  <router-link to="/places">Places</router-link>
  <router-link to="/demo/regions">Region Explorer</router-link>
  <router-link to="/lists">Lists</router-link>
</nav>
```

## Advanced Features

### 1. User Location Integration

Integrate with the existing geolocation system:

```vue
<script setup>
import { useGeolocation } from '@/composables/useGeolocation'

const { userLocation, getCurrentPosition } = useGeolocation()

// Pass user location to component
<RegionTwoColumnView
  :region-id="regionId"
  :user-location="userLocation"
  @place-selected="handlePlaceSelection"
/>
</script>
```

### 2. Authentication Integration

Show additional features for authenticated users:

```vue
<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Component automatically handles save buttons based on auth state
// No additional setup needed - SaveButton component handles this
</script>
```

### 3. Analytics Integration

Track user interactions:

```vue
<script setup>
const handlePlaceSelection = (place) => {
  // Analytics tracking
  if (place) {
    gtag('event', 'place_selected', {
      place_id: place.id,
      place_name: place.name,
      region_id: regionId.value,
      region_name: region.value?.name
    })
  }
}

const handleRegionLoad = (loadedRegion) => {
  gtag('event', 'region_viewed', {
    region_id: loadedRegion.id,
    region_name: loadedRegion.name,
    region_type: loadedRegion.type,
    place_count: loadedRegion.place_count
  })
}
</script>
```

## Production Deployment

### Environment Setup

Ensure your `.env` files have:

```env
# Development
VITE_MAPBOX_ACCESS_TOKEN=pk.your_dev_token

# Production
VITE_MAPBOX_ACCESS_TOKEN=pk.your_production_token
```

### Performance Optimization

1. **Enable Caching**:
```php
// In your controller
public function twoColumnView(Region $region)
{
    return Cache::remember("region_two_column_{$region->id}", 300, function() use ($region) {
        // ... your existing logic
    });
}
```

2. **Optimize Database Queries**:
```php
// Add database indexes
Schema::table('places', function (Blueprint $table) {
    $table->index(['region_id', 'status', 'latitude', 'longitude']);
    $table->index(['category_id', 'status']);
});
```

3. **CDN Integration**:
```php
// Optimize image URLs
$places = $places->map(function($place) {
    if ($place->cover_image_url) {
        $place->cover_image_url = Storage::url($place->cover_image_url);
    }
    return $place;
});
```

## Testing

### Unit Tests
```javascript
// tests/components/RegionTwoColumnView.test.js
import { mount } from '@vue/test-utils'
import RegionTwoColumnView from '@/components/regions/RegionTwoColumnView.vue'

describe('RegionTwoColumnView', () => {
  test('renders loading state initially', () => {
    const wrapper = mount(RegionTwoColumnView, {
      props: { regionId: 123 }
    })
    expect(wrapper.find('.animate-pulse')).toBeTruthy()
  })

  test('emits place-selected event', async () => {
    const wrapper = mount(RegionTwoColumnView, {
      props: { regionId: 123 }
    })
    
    // Simulate place selection
    await wrapper.vm.selectPlace({ id: 456, name: 'Test Place' })
    
    expect(wrapper.emitted('place-selected')).toBeTruthy()
  })
})
```

### API Tests
```php
// tests/Feature/RegionTwoColumnTest.php
class RegionTwoColumnTest extends TestCase
{
    public function test_returns_region_data()
    {
        $region = Region::factory()->create();
        $places = Place::factory()->count(5)->create(['region_id' => $region->id]);

        $response = $this->getJson("/api/region-map/{$region->id}/two-column");

        $response->assertOk()
            ->assertJsonStructure([
                'region' => ['id', 'name', 'type', 'slug'],
                'places' => [['id', 'name', 'latitude', 'longitude']],
                'has_next_page',
                'current_page'
            ]);
    }
}
```

## Common Issues & Solutions

### 1. Map Not Loading
- Check Mapbox token validity
- Verify CORS settings for production domain
- Ensure WebGL support in target browsers

### 2. Performance Issues
- Reduce places per page (max 50)
- Implement proper database indexing  
- Use Redis for API response caching

### 3. Mobile Experience
- Test on actual devices, not just browser dev tools
- Ensure proper touch event handling
- Optimize for slower network connections

### 4. SEO Considerations
- Add server-side rendering for region metadata
- Include structured data for places
- Implement proper meta tags and Open Graph

## Support & Maintenance

- **Component Location**: `/src/components/regions/RegionTwoColumnView.vue`
- **Documentation**: `/src/components/regions/README.md`
- **Demo**: `/src/views/regions/TwoColumnExample.vue`
- **Types**: `/src/types/regions.ts`

For issues or enhancement requests, check the existing documentation or create a new issue with reproduction steps.