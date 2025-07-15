# Profile Page Caching Optimization

This document describes the caching implementation for user profile pages to improve loading performance.

## Overview

User profile pages were taking 1-2 seconds to load due to multiple database queries and N+1 query issues. We've implemented a comprehensive caching solution that reduces load times to near-instant for cached data.

## Implementation Details

### Backend Caching

1. **UserProfileCacheService** (`app/Services/UserProfileCacheService.php`)
   - 10-minute cache duration for profile data
   - Caches user info, pinned lists, and recent lists
   - Provides cache warming and invalidation methods

2. **Updated UserProfileController** (`app/Http/Controllers/UserProfileController.php`)
   - Uses cache service for profile data retrieval
   - Clears cache on profile updates, follow/unfollow, and list pin/unpin
   - Warms cache after profile updates
   - Fixed N+1 query issues with proper eager loading

### Frontend Caching (Vue/Pinia)

1. **Profiles Store** (`frontend/src/stores/profiles.js`)
   - 5-minute client-side cache
   - Prevents redundant API calls
   - Supports cache invalidation and preloading

2. **Profile Component** (`frontend/src/views/profile/Show.vue`)
   - Uses Pinia store for data fetching
   - Watches route changes to refresh profiles
   - Supports force refresh option

## Configuration

### Development (File Cache)
The default cache driver is set to 'database' in `.env`:
```
CACHE_STORE=database
```

### Production (Redis Recommended)
For production, use Redis for better performance:
```
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Cache Invalidation

Cache is automatically cleared when:
- User updates their profile
- User follows/unfollows another user
- User pins/unpins a list
- Profile images are updated

## Performance Improvements

1. **Reduced Database Queries**
   - Profile stats use optimized count queries
   - Eager loading for relationships (lists, categories, items)
   - Limited item fetching (5 items per list)

2. **Query Optimizations**
   - Single query for profile stats instead of multiple
   - Proper indexes on frequently queried columns
   - Efficient relationship loading

## Usage

The caching is transparent to users and developers. Profile pages automatically use cached data when available, with fallback to direct database queries if cache misses occur.

### Manual Cache Management

Clear a specific user's cache:
```php
$cacheService->clearProfileCache($user);
```

Warm cache for a user:
```php
$cacheService->warmCache($user);
```

Clear all profile caches:
```php
$cacheService->clearAllProfileCaches();
```

## Monitoring

Monitor cache hit rates and performance through Laravel Telescope or your APM tool of choice.