# SPA Migration Guide

## Overview

This guide explains how to transition from the current Inertia.js architecture to a full SPA with Vue Router, addressing the route bloat and scalability concerns.

## Current Status

### Completed:
1. ✅ Vue Router installed and configured
2. ✅ Router configuration with dynamic routes for:
   - Region hierarchy: `/location/:state/:city/:neighborhood`
   - User profiles: `/:username`
   - User lists: `/:username/lists/:slug`
   - Places/Categories: `/places/:category/:subcategory/:slug`
3. ✅ API endpoints created for:
   - Regions (public and admin)
   - User profiles and lists
   - Directory entries
   - Route disambiguation
4. ✅ Pinia stores for state management
5. ✅ SPA entry point and configuration
6. ✅ Toggle command for switching modes

### Benefits of the New Architecture:

1. **Scalability**: Single catch-all route instead of thousands of Laravel routes
2. **Performance**: Faster route resolution, reduced memory usage
3. **Flexibility**: Easy to add new route patterns without touching Laravel
4. **SEO**: Can be enhanced with SSR/SSG if needed
5. **Developer Experience**: Clear separation between API and frontend

## Migration Steps

### 1. Enable SPA Mode

```bash
# Enable SPA mode
php artisan spa:toggle --enable

# Clear all caches
php artisan optimize:clear
```

### 2. Update Vite Configuration

Update `vite.config.js` to include the SPA entry point:

```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',        // Inertia version
                'resources/js/app-spa.js'      // SPA version
            ],
            // ...
        }),
    ],
});
```

### 3. Convert Vue Components

Components need to be updated to fetch data via API instead of receiving Inertia props:

#### Before (Inertia):
```vue
<script setup>
defineProps({
    region: Object,
    entries: Array
})
</script>
```

#### After (SPA):
```vue
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const region = ref(null)
const entries = ref([])

onMounted(async () => {
    const response = await axios.get(`/api/regions/${route.params.id}`)
    region.value = response.data.data
    
    const entriesResponse = await axios.get(`/api/regions/${route.params.id}/entries`)
    entries.value = entriesResponse.data.data
})
</script>
```

### 4. Update Authentication

The SPA uses Sanctum for authentication. Login/logout flows need to be updated:

```javascript
// Login
await axios.get('/sanctum/csrf-cookie')
await axios.post('/login', credentials)

// Get authenticated user
const response = await axios.get('/api/user')
```

### 5. Handle Route Disambiguation

For ambiguous routes (e.g., `/:slug` could be a user or a region), use the disambiguation endpoint:

```javascript
const response = await axios.get('/api/resolve-path', {
    params: { path: 'california' }
})
// Returns: { type: 'region', level: 1, data: { state: 'california' } }
```

## Testing the Migration

1. **Test API Endpoints**: 
   ```bash
   # Test region API
   curl http://localhost:8000/api/regions
   
   # Test disambiguation
   curl "http://localhost:8000/api/resolve-path?path=california"
   ```

2. **Test Frontend Routing**:
   - Visit `/location/california` (state)
   - Visit `/location/california/los-angeles` (city)
   - Visit `/john-doe` (user profile)
   - Visit `/john-doe/lists/favorites` (user list)

3. **Performance Testing**:
   ```bash
   # Before (Inertia)
   php artisan route:list | wc -l
   
   # After (SPA) - should show significantly fewer routes
   php artisan route:list | wc -l
   ```

## Rollback Plan

If you need to revert to Inertia:

```bash
# Disable SPA mode
php artisan spa:toggle --disable

# Clear caches
php artisan optimize:clear
```

## Next Steps

1. **Progressive Migration**: You can migrate routes incrementally by updating the catch-all route pattern
2. **SEO Optimization**: Consider adding server-side rendering with Nuxt.js or Inertia SSR
3. **API Optimization**: Add caching, pagination, and GraphQL if needed
4. **Testing**: Add E2E tests for critical user flows

## Common Issues

### CORS Issues
If you encounter CORS issues during development, update `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['http://localhost:5173'],
```

### Authentication Issues
Ensure your session configuration supports SPA:

```php
// config/session.php
'same_site' => 'lax',
```

### Route Not Found
If routes aren't working, ensure:
1. SPA mode is enabled
2. Caches are cleared
3. Vue Router is properly configured