# Mapbox Places Discovery Integration Guide

This guide provides comprehensive instructions for integrating the Mapbox-based places discovery system into your Vue 3 application.

## 🚀 Quick Start

### 1. Install Dependencies

```bash
# Install Mapbox GL JS and geocoder
npm install mapbox-gl @mapbox/mapbox-gl-geocoder

# Install utility dependencies (if not already installed)
npm install lodash
```

### 2. Add Mapbox CSS

Add the Mapbox CSS to your main CSS file or import it in your main.js:

```css
/* In your main CSS file (e.g., src/assets/main.css) */
@import 'mapbox-gl/dist/mapbox-gl.css';
@import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css';
```

Or in your main.js:
```javascript
import 'mapbox-gl/dist/mapbox-gl.css'
import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css'
```

### 3. Set Up Environment Variables

Add your Mapbox access token to your `.env` file:

```env
VITE_MAPBOX_ACCESS_TOKEN=pk.eyJ1IjoieW91ci11c2VybmFtZSIsImEiOiJjbHh4eHh4eHh4In0.your-token-here
```

**Get your Mapbox token:**
1. Sign up at [mapbox.com](https://www.mapbox.com/)
2. Go to your [Account page](https://account.mapbox.com/)
3. Copy your default public token or create a new one

### 4. Add Route

Add the new route to your Vue Router configuration:

```javascript
// In your router/index.js
import MapDiscovery from '@/views/places/MapDiscovery.vue'

const routes = [
  // ... your existing routes
  {
    path: '/places/discover',
    name: 'PlacesDiscovery',
    component: MapDiscovery,
    meta: {
      title: 'Discover Places',
      description: 'Find and explore places with our interactive map'
    }
  }
]
```

### 5. Update Package.json (Optional)

Update your package.json to include the new dependencies:

```json
{
  "dependencies": {
    "mapbox-gl": "^3.0.0",
    "@mapbox/mapbox-gl-geocoder": "^5.0.0",
    "lodash": "^4.17.21"
  }
}
```

## 🏗️ Architecture Overview

The places discovery system consists of several key components:

### Components
- **`MapDiscovery.vue`** - Main page component with split view layout
- **`PlacesMap.vue`** - Interactive Mapbox GL JS map with clustering
- **`PlacesList.vue`** - Filterable list of places with search
- **`PlaceCard.vue`** - Individual place display component
- **`FiltersPanel.vue`** - Comprehensive filtering interface

### Composables
- **`useMapbox.js`** - Map state management and utilities
- **`usePlacesDiscovery.js`** - Data fetching and filtering logic
- **`useGeolocation.js`** - User location detection with IP fallback

## 🎯 Features

### Map Features
- **Interactive Clustering**: Automatically groups nearby places for better performance
- **Custom Markers**: Different colors for featured, verified, and standard places
- **Smooth Animations**: Fluid transitions and hover effects
- **Responsive Controls**: Touch-friendly controls for mobile devices
- **Popup Details**: Rich place information in map popups

### Search & Filtering
- **Real-time Search**: Instant search with debouncing
- **Advanced Filters**: Category, distance, rating, price range, features
- **Location-based**: Automatic location detection with manual override
- **Sort Options**: Relevance, distance, rating, newest, alphabetical
- **Filter Persistence**: Maintains filters across view changes

### User Experience
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Accessibility**: Full keyboard navigation and screen reader support
- **Loading States**: Smooth loading indicators and error handling
- **Mobile Optimized**: Touch-friendly interface with bottom sheets

## 📱 View Modes

The discovery page supports three view modes:

1. **Split View** (Default): Map on right, list on left
2. **Map View**: Full-screen map experience
3. **List View**: Grid or list layout of places

## 🔧 Customization

### Map Styling

You can customize the map style by modifying the `useMapbox.js` composable:

```javascript
const defaultConfig = {
  style: 'mapbox://styles/mapbox/light-v11', // Change this
  center: [-74.006, 40.7128], // Default center point
  zoom: 12,
  // ... other options
}
```

Available Mapbox styles:
- `mapbox://styles/mapbox/streets-v12`
- `mapbox://styles/mapbox/outdoors-v12`
- `mapbox://styles/mapbox/light-v11`
- `mapbox://styles/mapbox/dark-v11`
- `mapbox://styles/mapbox/satellite-v9`
- `mapbox://styles/mapbox/satellite-streets-v12`

### API Integration

The system is designed to work with your existing Places API. Update the endpoints in `usePlacesDiscovery.js`:

```javascript
const API_BASE = '/api' // Change to your API base URL

// Customize these endpoints as needed:
// GET /api/places - List places with filters
// GET /api/places/nearby - Get nearby places
// GET /api/places/search - Search places
// GET /api/categories - Get place categories
// GET /api/regions - Get location regions
```

### Expected API Response Format

```javascript
// Places endpoint response
{
  "data": [
    {
      "id": 1,
      "name": "Place Name",
      "slug": "place-name",
      "description": "Place description",
      "category": {
        "id": 1,
        "name": "Restaurant",
        "slug": "restaurant"
      },
      "latitude": 40.7128,
      "longitude": -74.0060,
      "is_featured": true,
      "is_verified": true,
      "is_claimed": false,
      "average_rating": 4.5,
      "review_count": 123,
      "logo_url": "https://...",
      "cover_image_url": "https://...",
      "website_url": "https://...",
      "phone": "+1234567890",
      "canonical_url": "/places/state/city/category/place-name-1"
    }
  ],
  "current_page": 1,
  "last_page": 10,
  "total": 200
}
```

### Styling Customization

The components use Tailwind CSS classes. Customize the appearance by modifying the component templates or extending your Tailwind configuration:

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          500: '#3b82f6',
          600: '#2563eb',
          // ... your brand colors
        }
      }
    }
  }
}
```

## 🔒 Security & Performance

### Mapbox Token Security
- Use public tokens (starting with `pk.`) for client-side applications
- Restrict token usage to your domain in Mapbox account settings
- Monitor token usage to avoid unexpected charges

### Performance Optimizations
- **Clustering**: Automatically enabled for large datasets
- **Debounced Search**: Prevents excessive API calls
- **Lazy Loading**: Images and components load on demand
- **Infinite Scroll**: Loads more places as user scrolls
- **Viewport Filtering**: Only shows places in current map bounds

## 🚨 Troubleshooting

### Common Issues

**1. Map not loading**
```javascript
// Check console for token errors
// Verify token is set correctly in .env file
console.log('Token:', import.meta.env.VITE_MAPBOX_ACCESS_TOKEN)
```

**2. Permission denied errors during npm install**
```bash
# Try clearing npm cache
npm cache clean --force

# Or use yarn instead
yarn add mapbox-gl @mapbox/mapbox-gl-geocoder
```

**3. Geolocation not working**
- Ensure you're serving over HTTPS in production
- Check browser permissions for location access
- The system will fallback to IP-based location automatically

**4. Places not showing on map**
- Verify your API returns places with valid `latitude` and `longitude`
- Check that coordinates are numbers, not strings
- Ensure the API response format matches expected structure

### Development Tips

**Enable debug mode:**
```javascript
// In usePlacesDiscovery.js, add console logs
console.log('Fetched places:', places.value)
console.log('Filtered places:', filteredPlaces.value)
```

**Test with mock data:**
```javascript
// Create a mock places array for testing
const mockPlaces = [
  {
    id: 1,
    name: 'Test Place',
    latitude: 40.7128,
    longitude: -74.0060,
    category: { name: 'Restaurant' }
  }
]
```

## 📚 API Documentation

### Required Endpoints

Your backend should implement these endpoints:

```php
// Places Controller
GET    /api/places              // List places with filters
GET    /api/places/nearby       // Get places within radius
GET    /api/places/search       // Search places
GET    /api/places/{id}         // Get place details
GET    /api/categories          // List categories
GET    /api/regions             // List regions
```

### Query Parameters

**Places listing (`/api/places`):**
- `q` - Search query
- `category_id` - Filter by category
- `region_id` - Filter by region
- `lat`, `lng`, `radius` - Location-based filtering
- `bounds` - Viewport bounds filtering
- `min_rating` - Minimum rating filter
- `price_range` - Price range filter
- `open_now` - Only open places
- `features[]` - Array of required features
- `sort` - Sort order (relevance, distance, rating, newest, name)
- `page`, `per_page` - Pagination

## 🎨 UI/UX Guidelines

### Mobile Experience
- Touch-friendly controls (minimum 44px touch targets)
- Bottom sheet for place details on mobile
- Swipe gestures for map navigation
- Optimized for thumb navigation

### Accessibility
- ARIA labels on all interactive elements
- Keyboard navigation support
- Screen reader announcements
- High contrast mode support
- Focus management

### Performance
- Images lazy load with loading states
- Smooth transitions and animations
- Responsive to user actions
- Optimized for low-end devices

## 🔄 Integration with Existing Codebase

### Authentication Integration
```javascript
// In MapDiscovery.vue, add auth checks
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Show additional filters for authenticated users
const showStatusFilters = computed(() => authStore.user !== null)
```

### Notification Integration
The system uses your existing `useNotification` composable for user feedback.

### Router Integration
Place cards link to existing place detail pages using the `canonical_url` field.

## 📈 Analytics & Monitoring

Consider tracking these events:
- Map view/interaction events
- Search queries
- Filter usage
- Place clicks/views
- Location permission grants/denials

```javascript
// Example analytics integration
const trackEvent = (event, properties) => {
  // Your analytics service (Google Analytics, Mixpanel, etc.)
  if (window.gtag) {
    window.gtag('event', event, properties)
  }
}

// In component methods
const selectPlace = (place) => {
  trackEvent('place_selected', {
    place_id: place.id,
    place_name: place.name,
    view_mode: viewMode.value
  })
  // ... rest of method
}
```

## 🚀 Deployment

### Build Configuration

Ensure your build process includes the environment variables:

```bash
# Build command
VITE_MAPBOX_ACCESS_TOKEN=your-token npm run build
```

### CDN Considerations

Mapbox GL JS loads additional resources. Ensure your CSP allows:
```
script-src 'self' blob:
worker-src blob:
child-src blob:
img-src data: blob:
```

---

## 📞 Support

For issues with:
- **Mapbox GL JS**: [Mapbox Documentation](https://docs.mapbox.com/mapbox-gl-js/)
- **Component Integration**: Check the component documentation in each file
- **API Integration**: Ensure your API follows the expected response format

Enjoy building amazing places discovery experiences! 🗺️✨