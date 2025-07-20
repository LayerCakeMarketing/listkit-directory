# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Table of Contents
1. [Project Overview](#project-overview)
2. [Goals & Objectives](#goals--objectives)
3. [Core Features](#core-features)
4. [Technical Architecture](#technical-architecture)
5. [Development Setup](#development-setup)
6. [Important Patterns](#important-patterns)
7. [Development Guidelines](#development-guidelines)
8. [Performance Optimization](#performance-optimization)
9. [Testing Strategy](#testing-strategy)
10. [Deployment & Infrastructure](#deployment--infrastructure)

## Project Overview

**Listerino / ListKit** is a modern web application that blends local business discovery with advanced, multimedia list management. Designed for both individuals and organizations, the platform empowers users to create and share curated lists, build branded channels, and engage communities around shared interests.

### Vision
To become the premier platform for discovering, organizing, and sharing local information through user-curated lists and community-driven content, fostering meaningful connections between users, businesses, and creators.

### Tech Stack
- **Backend**: Laravel 11, PostgreSQL 15+, Redis
- **Frontend**: Vue.js 3 (Composition API), Vite, Tailwind CSS
- **Authentication**: Laravel Sanctum (SPA mode)
- **Media**: Cloudflare Images
- **Search**: PostgreSQL Full-Text Search
- **Real-time**: Laravel Echo (future)

### Target Metrics
- 10,000 active users within 6 months
- 50,000 user-created lists in first year
- 60% monthly active user retention

## Goals & Objectives

### Primary Goals
1. **Discovery**: Help users find relevant local businesses and services
2. **Organization**: Enable users to create and manage curated multimedia lists
3. **Community**: Foster engagement through channels, collaborations, and social interactions
4. **Monetization**: Provide revenue opportunities via subscriptions, affiliates, and tips (10% platform fee)

### User Personas
1. **Local Explorer** - Seeks trusted recommendations and easy list creation
2. **Content Creator** - Needs monetization tools and analytics
3. **Business Owner** - Wants to manage presence and engage customers
4. **Community Organizer** - Requires collaborative tools and member management

## Core Features

### 1. User Management
```php
// Models: User, Profile, Follow, SavedItem
// Controllers: ProfileController, UserProfileController
// Routes: /profile/*, /@{username}
```
- Custom URLs (/@username)
- Avatar and cover images via Cloudflare
- Privacy settings and activity feeds
- Two-factor authentication (planned)

### 2. Lists & Collections
```php
// Models: UserList (polymorphic owner), ListItem, ListCategory
// Controllers: UserListController, PublicListController
// Routes: /lists/*, /mylists, /@{username}/{slug}
```
- **Polymorphic Ownership**: Lists owned by Users or Channels
- **Rich Content**: Markdown descriptions, cover images, tags
- **Collaboration**: Shared editing, version history
- **Privacy**: Public, unlisted, or private visibility
- **Features**: Scheduled publishing, voting, "lists of lists"

### 3. Channels
```php
// Models: Channel, ChannelMember, Post
// Controllers: ChannelController
// Routes: /channels/*, /@{channelSlug}
```
- Custom branding and URLs
- Member roles and permissions
- Content monetization options
- Analytics dashboard
- Event management

### 4. Places & Directory
```php
// Models: Place, Region, Category
// Controllers: PlaceController, RegionController
// Routes: /places/*, /regions/*, /admin/regions/*
```
- Hierarchical categories and regions
- Business claiming process
- Rich media galleries
- Operating hours and contact info
- Review system (planned)

### 5. Regions & Location Hierarchy
```php
// Model: Region (hierarchical with parent_id)
// Controller: RegionController, Admin\RegionManagementController
// Routes: /regions/*, /state/*, /state/city/*, /state/city/neighborhood/*
```

#### Region Structure
```sql
-- Region Types (hierarchical)
'country'       → Top level (e.g., USA)
'state'         → State/Province (e.g., California)
'city'          → City/Town (e.g., San Francisco)
'neighborhood'  → Neighborhood/District (e.g., Mission District)

-- Database Structure
regions (
    id, 
    name,           -- Display name (e.g., "San Francisco")
    slug,           -- URL-friendly (e.g., "san-francisco")
    type,           -- Region type (state/city/neighborhood)
    parent_id,      -- Parent region (nullable for countries)
    full_path,      -- Cached path (e.g., "california/san-francisco")
    lat/lng,        -- Center coordinates
    bounds,         -- PostgreSQL polygon for boundaries
    population,     -- Population count
    meta_data       -- JSON field for additional data
)
```

#### URL Routing Pattern
```php
// Frontend routes (SEO-friendly)
/california                     → State page
/california/san-francisco       → City page  
/california/san-francisco/mission-district → Neighborhood page

// API endpoints
GET /api/regions/search         → Search regions by name
GET /api/regions/by-slug/{slug} → Get region by slug
GET /api/regions/{id}/children  → Get child regions
GET /api/regions/popular        → Popular regions

// Admin endpoints
GET    /api/admin/regions       → List all regions (paginated)
POST   /api/admin/regions       → Create new region
PUT    /api/admin/regions/{id}  → Update region
DELETE /api/admin/regions/{id}  → Delete region
POST   /api/admin/regions/bulk  → Bulk operations
```

#### Region Features
- **Hierarchical Navigation**: Browse from country → state → city → neighborhood
- **Smart Slugs**: Auto-generated URL-friendly slugs with uniqueness
- **Geocoding**: Automatic lat/lng lookup via geocoding service
- **Boundaries**: Support for geographic boundaries (PostgreSQL PostGIS)
- **Statistics**: Place counts, user activity, popular lists per region
- **Featured Content**: Curated lists and places per region
- **SEO Optimization**: Meta tags, breadcrumbs, structured data

#### Admin Region Management (/admin/regions)
```php
// Features available in admin panel
- Create/Edit/Delete regions
- Set parent-child relationships
- Bulk import from CSV
- Geocode addresses
- Manage featured content
- View statistics and analytics
- Set custom meta data
- Manage region-specific settings
```

### 6. Search & Discovery
```php
// PostgreSQL full-text search with tsvector
// Faceted filtering, location radius
// Personalized recommendations (planned)
```

### 7. Media Management
```php
// Service: CloudflareImageService
// Features: Drag-drop upload, auto-optimization, galleries
```

## Technical Architecture

### Database Schema
```sql
-- Core Tables
users (id, name, username, custom_url, email, avatar, ...)
channels (id, name, slug, user_id, description, ...)
lists (id, name, slug, owner_type, owner_id, visibility, ...)
list_items (id, list_id, itemable_type, itemable_id, order_index, ...)
places (id, name, slug, category_id, region_id, ...)
regions (id, name, slug, parent_id, type, ...)

-- Polymorphic Relationships
owner_type/owner_id → User or Channel
itemable_type/itemable_id → Place, Post, or custom content

-- Indexes for Performance
lists: (owner_type, owner_id), (visibility, status)
places: (category_id), (region_id), search_vector
```

### API Architecture
```php
// RESTful endpoints with consistent patterns
GET    /api/resource       → index (paginated)
POST   /api/resource       → store
GET    /api/resource/{id}  → show
PUT    /api/resource/{id}  → update
DELETE /api/resource/{id}  → destroy

// Nested resources
/api/lists/{list}/items
/api/channels/{channel}/posts
```

### Frontend Architecture
```javascript
// Vue 3 Composition API with TypeScript (future)
// Component structure:
/views          → Page components
/components     → Reusable UI components
/composables    → Shared logic
/stores         → Pinia stores (future)
/utils          → Helper functions
```

## Development Setup

### Database Configuration

#### Important: Hybrid Setup
- **Local PostgreSQL**: Application data in `illum_local` database
- **Docker PostgreSQL**: Container included but NOT used for data

#### Configuration Files
1. **`.env`** - Local development
   ```env
   DB_HOST=localhost
   DB_DATABASE=illum_local
   SESSION_DRIVER=database
   SPA_MODE=true
   ```

2. **`.env.docker`** - Docker containers
   ```env
   DB_HOST=docker.for.mac.localhost  # macOS
   DB_DATABASE=illum_local
   ```

### Development Commands
```bash
# Start all services
composer dev

# Individual services
php artisan serve              # Laravel server
npm run dev                    # Vite dev server
php artisan queue:listen       # Queue worker
php artisan pail               # Log viewer

# Database
php artisan migrate           # Run migrations
php artisan db:seed          # Seed test data
php artisan migrate:fresh --seed  # Reset and seed

# Code quality
./vendor/bin/pint            # Format code
./vendor/bin/phpstan analyse # Static analysis
php artisan test             # Run tests
```

## Important Patterns

### 1. Polymorphic Ownership
```php
// Lists can be owned by Users or Channels
$list->owner_type = User::class; // or Channel::class
$list->owner_id = $user->id;

// Check ownership
if ($list->owner_type !== User::class || $list->owner_id !== $user->id) {
    abort(403);
}
```

### 2. SPA Authentication
```php
// Using Laravel Sanctum in SPA mode
// Middleware configuration in bootstrap/app.php
$middleware->appendToGroup('api', [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
]);
```

### 3. Efficient Eager Loading
```php
// Avoid N+1 queries
$lists = UserList::with([
    'owner',  // Polymorphic - loads User or Channel
    'category:id,name,slug,color',
    'user:id,name,username,avatar'
])->withCount('items')->paginate(12);

// Don't load unnecessary relations
// BAD: ->with('items') when you only need count
// GOOD: ->withCount('items')
```

### 4. Caching Strategy
```php
// Cache expensive queries
$categories = Cache::remember('public_categories', 1800, function () {
    return Category::active()
        ->withCount(['lists' => function ($q) {
            $q->public()->active();
        }])
        ->get();
});

// Clear cache on updates
Cache::forget('public_categories');
```

### 5. Image Handling
```php
// Cloudflare Images integration
$imageService = app(CloudflareImageService::class);
$result = $imageService->uploadImage($file);
$imageUrl = $result['url'];

// Always store Cloudflare ID for deletion
$model->avatar_cloudflare_id = $result['id'];
```

### 6. Region Hierarchy Management
```php
// Creating regions with proper hierarchy
$california = Region::create([
    'name' => 'California',
    'slug' => 'california',
    'type' => 'state',
    'parent_id' => null
]);

$sanFrancisco = Region::create([
    'name' => 'San Francisco',
    'slug' => 'san-francisco',
    'type' => 'city',
    'parent_id' => $california->id,
    'full_path' => 'california/san-francisco'
]);

// Querying with hierarchy
$region = Region::with('parent', 'children')
    ->whereSlug('san-francisco')
    ->first();

// Get full path
$path = $region->getFullPath(); // "california/san-francisco"

// Get breadcrumbs
$breadcrumbs = $region->getBreadcrumbs(); // Collection of parent regions

// Scope queries
Region::ofType('city')
    ->withPlaceCount()
    ->orderBy('place_count', 'desc')
    ->get();
```

## Development Guidelines

### 1. Frontend Guidelines
- Use Composition API for all new components
- Follow Vue 3 best practices (script setup)
- Implement loading states and error handling
- Use Tailwind utility classes, avoid custom CSS
- Optimize images with Cloudflare variants

### 2. Backend Guidelines
- Follow Laravel conventions and best practices
- Use Form Requests for validation
- Implement proper authorization (Gates/Policies)
- Write comprehensive tests for new features
- Use database transactions for critical operations

### 3. Database Guidelines
- Always add indexes for foreign keys
- Use composite indexes for common query patterns
- Implement soft deletes where appropriate
- Use PostgreSQL-specific features when beneficial
- Write both up() and down() migration methods

### 4. API Guidelines
- Return consistent JSON structures
- Use proper HTTP status codes
- Implement pagination for list endpoints
- Include related data efficiently
- Version APIs when making breaking changes

## Performance Optimization

### 1. Query Optimization
```php
// Use query scopes
UserList::searchable()->active()->withCount('items');

// Select only needed columns
->select('id', 'name', 'slug', 'created_at')

// Use database indexes effectively
// Check with: EXPLAIN ANALYZE <query>
```

### 2. Frontend Optimization
```javascript
// Lazy load components
const UserProfile = () => import('@/views/UserProfile.vue')

// Debounce search inputs
const debouncedSearch = debounce(search, 300)

// Use intersection observer for infinite scroll
const observer = new IntersectionObserver(loadMore)
```

### 3. Caching Layers
- **Database Query Cache**: Redis (production)
- **HTTP Cache**: Cloudflare CDN
- **Application Cache**: Laravel Cache facade
- **Frontend Cache**: Browser localStorage for user preferences

## Testing Strategy

### 1. Test Structure
```
tests/
├── Feature/
│   ├── Auth/          # Authentication flows
│   ├── Lists/         # List CRUD operations
│   ├── Channels/      # Channel functionality
│   └── Api/           # API endpoints
├── Unit/
│   ├── Models/        # Model methods
│   ├── Services/      # Service classes
│   └── Helpers/       # Helper functions
└── Browser/ (Dusk)    # E2E tests (future)
```

### 2. Testing Patterns
```php
// Feature test example
public function test_user_can_create_list()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/api/lists', [
            'name' => 'My Test List',
            'visibility' => 'public',
        ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('lists', [
        'name' => 'My Test List',
        'owner_type' => User::class,
        'owner_id' => $user->id,
    ]);
}
```

## Deployment & Infrastructure

### Production Stack
- **Hosting**: Cloudways/DigitalOcean VPS
- **Database**: PostgreSQL 15+ with pgBouncer
- **Cache**: Redis with persistence
- **Queue**: Redis + Laravel Horizon
- **Storage**: Local + Cloudflare Images
- **CDN**: Cloudflare
- **Monitoring**: Laravel Telescope + Sentry

### Deployment Process
```bash
# GitHub Actions workflow
1. Run tests
2. Build assets (npm run build)
3. Deploy to staging
4. Run migrations
5. Clear caches
6. Warm caches
7. Health checks
8. Deploy to production
```

### Environment Variables
```env
# Production essentials
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
FORCE_HTTPS=true

# Services
CLOUDFLARE_ACCOUNT_ID=xxx
REDIS_HOST=xxx
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
```

### Monitoring & Alerts
- **Application**: Laravel Telescope
- **Errors**: Sentry integration
- **Uptime**: Cloudflare Analytics
- **Database**: PostgreSQL slow query log
- **Queue**: Horizon metrics

## Common Issues & Solutions

### 1. Authentication Issues
```bash
# Session not persisting
- Check SESSION_DRIVER=database
- Verify session cookie not encrypted
- Check SANCTUM_STATEFUL_DOMAINS
```

### 2. Performance Issues
```bash
# Slow page loads
- Check for N+1 queries
- Verify indexes exist
- Enable query caching
- Optimize eager loading
```

### 3. Docker Issues
```bash
# Database connection errors
- Use docker.for.mac.localhost on macOS
- Check .env.docker is used
- Verify PostgreSQL accepts connections
```

## Future Development Goals

### 🗺️ Mapping & Geolocation
- **Mapbox Integration**: Replace current Leaflet with Mapbox for advanced features
  - Interactive maps with custom styles and 3D buildings
  - Multi-stop route planning and optimization
  - Turn-by-turn directions between list locations
  - Clustering for dense areas with smooth zoom transitions
  - Geofencing for location-based notifications
  - Heatmaps to visualize popular areas
- **Implementation Path**:
  ```javascript
  // New components to create
  components/maps/MapboxMap.vue
  components/maps/RouteBuilder.vue
  components/maps/LocationCluster.vue
  composables/useMapbox.js
  ```

### 🔍 Search & Discovery Enhancement
- **Meilisearch Integration**: Replace PostgreSQL full-text search for better performance
  - Typo tolerance and fuzzy matching
  - Instant search with sub-50ms response times
  - Faceted search with dynamic filters
  - Multi-language support with stemming
  - Search analytics and popular queries
- **Technical Implementation**:
  ```php
  // Backend services
  app/Services/MeilisearchService.php
  app/Jobs/IndexSearchableContent.php
  app/Console/Commands/MeilisearchSync.php
  config/meilisearch.php
  
  // API endpoints
  POST /api/search/instant
  GET  /api/search/suggestions
  POST /api/search/filters
  ```

### 🏪 Places Directory Enhancements
- **Claim Ownership System**:
  ```php
  // Database schema
  CREATE TABLE place_claims (
      id SERIAL PRIMARY KEY,
      place_id INTEGER REFERENCES places(id),
      user_id INTEGER REFERENCES users(id),
      verification_method VARCHAR(50),
      verification_data JSONB,
      verified_at TIMESTAMP,
      status VARCHAR(20)
  );
  
  // Verification methods
  - Email domain matching
  - Phone verification via SMS
  - Business document upload
  - Google My Business integration
  - Manual review process
  ```
- **Enhanced Place Features**:
  - Virtual tour integration (Matterport/360°)
  - Dynamic pricing/menu management
  - Real-time availability/booking
  - Customer Q&A with moderation
  - Business insights dashboard
  - Competitor analysis tools

### 📦 Advanced List Features
- **Flexible List Templates**:
  ```javascript
  // Template system architecture
  resources/js/components/lists/templates/
    ├── TravelItinerary.vue      // Multi-day trips with schedules
    ├── ShoppingList.vue          // Price tracking, availability
    ├── HowToGuide.vue           // Step-by-step tutorials
    ├── RestaurantCollection.vue  // Cuisine filters, price ranges
    ├── EventSchedule.vue         // Time-based organization
    └── ProductComparison.vue     // Feature matrices
  ```
- **Rich Content Support**:
  - **Video Integration**: Upload to Cloudflare Stream, embed YouTube/Vimeo
  - **Audio Features**: Narration recording, podcast embeds
  - **Interactive Elements**: Polls, quizzes, calculators
  - **Advanced Editor**: Code snippets, tables, diagrams
- **Collaboration Features**:
  - Real-time collaborative editing (Y.js/CRDT)
  - Contributor roles and permissions
  - Change tracking and version control
  - Inline comments and discussions
  - Merge request workflow

### 💸 Monetization Implementation
- **Subscription System Architecture**:
  ```php
  // Core tables
  CREATE TABLE subscription_tiers (
      id SERIAL PRIMARY KEY,
      channel_id INTEGER REFERENCES channels(id),
      name VARCHAR(100),
      price DECIMAL(10,2),
      interval VARCHAR(20), -- monthly/yearly
      benefits JSONB
  );
  
  CREATE TABLE subscriptions (
      id SERIAL PRIMARY KEY,
      user_id INTEGER REFERENCES users(id),
      channel_id INTEGER REFERENCES channels(id),
      tier_id INTEGER REFERENCES subscription_tiers(id),
      stripe_subscription_id VARCHAR(255),
      status VARCHAR(50),
      current_period_end TIMESTAMP
  );
  
  CREATE TABLE revenue_shares (
      id SERIAL PRIMARY KEY,
      channel_id INTEGER REFERENCES channels(id),
      amount DECIMAL(10,2),
      platform_fee DECIMAL(10,2), -- 10%
      creator_amount DECIMAL(10,2), -- 90%
      period_start DATE,
      period_end DATE,
      paid_at TIMESTAMP
  );
  ```
- **Revenue Features**:
  - **Tipping System**: One-time support with 90/10 split
  - **Subscription Tiers**: 
    - Basic ($4.99/mo): Ad-free, early access
    - Pro ($9.99/mo): Exclusive content, downloads
    - VIP ($19.99/mo): Direct access, custom requests
  - **Affiliate Integration**: 
    - Amazon Associates API
    - ShareASale integration
    - Custom affiliate links
    - Revenue tracking dashboard
  - **Sponsored Content**:
    - Brand partnership tools
    - Disclosure compliance
    - Performance metrics
- **Platform Business Model**:
  - 10% transaction fee on all creator earnings
  - Premium features for creators ($29/mo)
  - Enterprise API access ($299/mo)
  - White-label solutions (custom pricing)

### 🔧 Technical Implementation Roadmap

#### Phase 1 - Foundation (Next Sprint)
1. **Meilisearch Setup**
   ```bash
   # Docker setup
   docker run -p 7700:7700 getmeili/meilisearch
   
   # Laravel integration
   composer require meilisearch/meilisearch-php
   php artisan meilisearch:index
   ```

2. **Basic Claim Flow**
   ```php
   // Routes
   POST /api/places/{id}/claim
   GET  /api/places/{id}/claim-status
   POST /api/places/{id}/verify
   
   // Controllers
   ClaimController@initiate
   ClaimController@verify
   ClaimController@status
   ```

3. **Tip Jar MVP**
   ```javascript
   // Components
   components/monetization/TipJar.vue
   components/monetization/TipSuccess.vue
   
   // Stripe integration
   const { error } = await stripe.confirmPayment({
     elements,
     confirmParams: {
       return_url: `/tips/success`,
     },
   });
   ```

#### Phase 2 - Enhanced Features (Q2 2025)
- Mapbox full integration
- Channel subscription tiers
- Advanced list templates
- Collaborative editing beta

#### Phase 3 - Scale & Optimize (Q3 2025)
- Mobile app development
- AI content recommendations
- Advanced analytics platform
- International payment methods

#### Phase 4 - Enterprise & Expansion (Q4 2025)
- White-label solutions
- API marketplace
- Advanced ML features
- Global CDN optimization

### 📊 Success Metrics
- **Search Performance**: <50ms response time
- **Map Engagement**: 60% of users interact with maps
- **Monetization**: 5% of creators earn >$100/month
- **Claim Rate**: 30% of businesses claim listings
- **List Templates**: 40% adoption rate

---

**Last Updated**: July 2025
**Maintained By**: Development Team
**Version**: 2.1