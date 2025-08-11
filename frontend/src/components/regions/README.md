# RegionTwoColumnView Component

A comprehensive Vue 3 component that displays regions (neighborhoods, parks, cities) with a responsive two-column layout featuring an interactive places list and integrated Mapbox map.

## Features

### 🎨 **Modern UI/UX**
- **Responsive Design**: Adapts to mobile (stacked) and desktop (two-column) layouts
- **Interactive Elements**: Hover effects, smooth transitions, and loading states
- **Accessibility**: Full keyboard navigation, ARIA labels, and screen reader support
- **Visual Feedback**: Loading skeletons, error states, and empty state messages

### 🗺️ **Advanced Mapping**
- **Mapbox Integration**: High-performance maps with clustering support
- **Interactive Markers**: Click markers for place details, hover effects
- **Region Boundaries**: Display region bounds when available
- **Map Controls**: Zoom, geolocation, fit bounds, and search in viewport

### 🔍 **Smart Filtering & Search**
- **Real-time Search**: Debounced search across place names, descriptions, and categories
- **Category Filtering**: Dynamic category buttons with place counts
- **Infinite Scroll**: Performance-optimized loading of large place datasets
- **Search State Management**: Maintains search/filter state during navigation

### 📱 **Mobile Optimization**
- **Collapsible Sections**: Toggle between list and map views on mobile
- **Sticky Headers**: Important navigation elements stay accessible
- **Touch-Friendly**: Optimized touch targets and gestures
- **Performance**: Lazy loading and optimized rendering

## Usage

### Basic Implementation

```vue
<template>
  <div class="h-screen">
    <RegionTwoColumnView
      :region-id="regionId"
      @place-selected="handlePlaceSelection"
      @region-loaded="handleRegionLoad"
    />
  </div>
</template>

<script setup>
import RegionTwoColumnView from '@/components/regions/RegionTwoColumnView.vue'

const regionId = 123
const handlePlaceSelection = (place) => console.log('Selected:', place)
const handleRegionLoad = (region) => console.log('Loaded:', region)
</script>
```

### Advanced Usage with Route Integration

```vue
<template>
  <RegionTwoColumnView
    :key="route.params.regionId"
    :region-id="parseInt(route.params.regionId)"
    @place-selected="navigateToPlace"
    @region-loaded="updatePageTitle"
  />
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const navigateToPlace = (place) => {
  if (place) {
    router.push(`/places/${place.slug}`)
  }
}

const updatePageTitle = (region) => {
  document.title = `${region.name} - Places Directory`
}
</script>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `regionId` | `Number` | `undefined` | ID of the region to display. Can be dynamically changed. |

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `placeSelected` | `Place \| null` | Emitted when a place is selected/deselected |
| `regionLoaded` | `Region` | Emitted when region data is successfully loaded |

## API Integration

The component expects the following API endpoints to be available:

### Primary Endpoint
```
GET /api/region-map/{regionId}/two-column
```

**Response Structure:**
```json
{
  "region": {
    "id": 123,
    "name": "San Francisco",
    "type": "city",
    "slug": "san-francisco",
    "description": "...",
    "place_count": 1248,
    "population": 873000,
    "bounds": {
      "north": 37.8324,
      "south": 37.7049,
      "east": -122.3482,
      "west": -122.5277
    },
    "park_info": { // Only for parks
      "hours": "24 hours",
      "entrance_fee": "$35 per vehicle",
      "activities": ["Hiking", "Camping"]
    }
  },
  "places": [...],
  "has_next_page": true,
  "current_page": 1
}
```

### Places Endpoint
```
GET /api/region-map/{regionId}/places?page=1&search=query&category=123
```

**Response Structure:**
```json
{
  "places": [
    {
      "id": 456,
      "name": "Golden Gate Park",
      "slug": "golden-gate-park",
      "latitude": 37.7694,
      "longitude": -122.4862,
      "category": {
        "id": 12,
        "name": "Parks & Recreation",
        "color": "#22c55e"
      },
      "is_featured": true,
      "is_verified": true,
      "cover_image_url": "...",
      "description": "...",
      "address": "...",
      "website_url": "...",
      "distance": 2.3
    }
  ],
  "has_next_page": false,
  "current_page": 1
}
```

## Data Structures

### Region Types
- `city` - Urban areas (e.g., San Francisco)
- `neighborhood` - City districts (e.g., Mission District)  
- `state_park` - State parks with facilities
- `national_park` - National parks with services
- `state` - State-level regions (e.g., California)

### Place Properties
- **Required**: `id`, `name`, `latitude`, `longitude`
- **Optional**: `description`, `category`, `images`, `ratings`, `contact_info`
- **Computed**: `distance` (calculated from user location)

## Styling & Customization

### CSS Variables
```css
:root {
  --region-sidebar-width: 40%; /* Desktop sidebar width */
  --region-map-width: 60%; /* Desktop map width */
  --region-mobile-breakpoint: 768px; /* Mobile breakpoint */
}
```

### Custom Themes
The component uses Tailwind CSS classes but can be customized:

```vue
<style scoped>
/* Custom category badge colors */
.category-badge-restaurants {
  @apply bg-red-100 text-red-800;
}

.category-badge-shopping {
  @apply bg-blue-100 text-blue-800;
}

/* Custom loading states */
.custom-skeleton {
  @apply animate-pulse bg-gradient-to-r from-gray-200 to-gray-300;
}
</style>
```

## Performance Optimization

### Built-in Optimizations
- **Virtual Scrolling**: Handles large place lists efficiently
- **Debounced Search**: 300ms delay to prevent excessive API calls
- **Map Clustering**: Automatic clustering for dense marker areas
- **Lazy Loading**: Images and content loaded on demand
- **Caching**: API responses cached for better UX

### Performance Tips
1. **Set appropriate page sizes** (recommended: 20-50 places per page)
2. **Use region bounds** for better initial map positioning  
3. **Optimize images** with appropriate CDN settings
4. **Monitor bundle size** if adding heavy dependencies

## Accessibility Features

### Keyboard Navigation
- `Tab/Shift+Tab` - Navigate between interactive elements
- `Enter/Space` - Activate buttons and select places
- `Escape` - Close modals and deselect items
- `Arrow Keys` - Navigate within lists (when focused)

### Screen Reader Support
- Comprehensive ARIA labels and descriptions
- Live regions for dynamic content updates
- Proper heading hierarchy (h1-h6)
- Alt text for all images

### Focus Management
- Visible focus indicators
- Logical tab order
- Focus trapping in modals
- Skip links for main content

## Browser Support

- **Modern Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Mobile**: iOS Safari 14+, Chrome Mobile 90+
- **Dependencies**: Mapbox GL JS requires WebGL support

## Troubleshooting

### Common Issues

**1. Map not loading**
```javascript
// Check Mapbox token in .env
VITE_MAPBOX_ACCESS_TOKEN=pk.your_token_here

// Verify token has proper permissions for:
// - Maps:Read
// - Geocoding:Read (if using geocoding features)
```

**2. Places not displaying**
```javascript
// Ensure API endpoint returns proper structure
// Check browser console for API errors
// Verify place coordinates are valid (not 0,0)
```

**3. Mobile responsiveness issues**
```css
/* Ensure parent container has proper height */
.region-container {
  height: 100vh; /* or calc(100vh - header-height) */
}
```

**4. Performance issues with large datasets**
```javascript
// Reduce page size
const PLACES_PER_PAGE = 20; // instead of 100

// Enable clustering at higher zoom levels
clusterMaxZoom: 12 // instead of 14
```

## Examples

Check out the complete working example at:
- **Demo**: `/src/views/regions/TwoColumnExample.vue`
- **Live Preview**: Available in development mode

## Contributing

When contributing to this component:

1. **Maintain accessibility** - Test with screen readers
2. **Test mobile devices** - Verify responsive behavior
3. **Performance testing** - Test with large datasets (1000+ places)
4. **API compatibility** - Ensure backend changes are compatible
5. **Documentation** - Update this README for new features

## Dependencies

- **Vue 3.4+** - Composition API, script setup
- **Vue Router 4.5+** - Navigation and route parameters  
- **Heroicons 2.0+** - Icon components
- **Mapbox GL JS 3.1+** - Interactive maps
- **Lodash 4.17+** - Utility functions (debounce)

## License

This component is part of the Listerino project and follows the project's licensing terms.