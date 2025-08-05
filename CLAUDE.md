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
11. [Common Commands](#common-commands)

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

### Local Development Configuration
- **Database**: PostgreSQL 15+ (native installation)
- **PHP**: 8.3+ (native installation)
- **Node.js**: 18+ (for frontend development)
- **Redis**: Optional for local development

#### Configuration Files
**`.env`** - Local development settings
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
SPA_MODE=true

# Local PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=illum_local
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Development settings
SESSION_DRIVER=database
CACHE_STORE=array
QUEUE_CONNECTION=database
```

### Development Commands
```bash
# Start all services (recommended)
composer dev

# Individual services
php artisan serve              # Laravel server (port 8000)
npm run dev --prefix frontend  # Vite dev server (port 5173)
php artisan queue:listen       # Queue worker
php artisan pail               # Log viewer

# Database
php artisan migrate           # Run migrations
php artisan db:seed          # Seed test data
php artisan migrate:fresh --seed  # Reset and seed

# Code quality
./vendor/bin/pint            # Format code
php artisan test             # Run tests

# Frontend build
cd frontend && npm run build && cd ..  # Build frontend for production
```

### Production Environment
Production uses Docker containers managed via docker-compose:
- **listerino_app**: Laravel application (webdevops/php-nginx:8.3)
- **listerino_db**: PostgreSQL database (postgis/postgis:15-3.4)
- **listerino_redis**: Cache and sessions (redis:7-alpine)
- **nginx**: Reverse proxy (native installation)

**Important**: Production uses `docker-compose.production.yml` as the primary configuration.

See [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md) for detailed infrastructure documentation.

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
- **Hosting**: DigitalOcean VPS (137.184.113.161)
- **Architecture**: Docker Compose with multiple containers
- **Database**: PostgreSQL 15+ with PostGIS (Docker container)
- **Cache**: Redis (Docker container)
- **Web Server**: Nginx (native) as reverse proxy
- **SSL**: Let's Encrypt with auto-renewal
- **Storage**: Local + Cloudflare Images
- **CDN**: Cloudflare

### Docker Services (Production)
```yaml
# docker-compose.yml services
listerino_app:     # Laravel application (port 8001)
listerino_db:      # PostgreSQL database (port 5432)
listerino_redis:   # Cache and sessions (port 6379)
listerino_frontend: # Development only (port 5174)
```

### Deployment Process

#### Automated Deployment (GitHub Actions)
```bash
# Push to main branch triggers automatic deployment
git add .
git commit -m "feat: your feature description"
git push listerino main

# Monitor deployment at:
https://github.com/lcreative777/listerino/actions
```

#### Manual Deployment
```bash
# Using deploy-simple.sh script
1. Build frontend locally (npm run build)
2. Create deployment archive
3. Transfer files to production
4. Update Docker environment
5. Run migrations
6. Clear and rebuild caches
7. Restart services

# Quick deployment command
./deploy-simple.sh
```

#### Database Migrations
```bash
# Migrations run automatically via GitHub Actions
# For manual migration:
ssh root@137.184.113.161
cd /var/www/listerino
docker exec -w /app listerino_app php artisan migrate --force
```

See [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) for detailed procedures.

### Production Environment Variables
```env
# Application settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://listerino.com
SPA_MODE=true

# Database (Docker internal network)
DB_CONNECTION=pgsql
DB_HOST=listerino_db
DB_PORT=5432
DB_DATABASE=listerino
DB_USERNAME=listerino
DB_PASSWORD=securepassword123

# Redis (Docker internal network)
REDIS_HOST=listerino_redis
SESSION_DRIVER=redis
CACHE_STORE=redis

# Security
SESSION_DOMAIN=.listerino.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=listerino.com,www.listerino.com
FORCE_HTTPS=true

# Services
CLOUDFLARE_ACCOUNT_ID=xxx
CLOUDFLARE_IMAGES_TOKEN=xxx
```

### Monitoring & Maintenance
- **Logs**: Docker logs + Laravel logs
- **Monitoring**: `docker stats` for resource usage
- **Backups**: Automated PostgreSQL dumps
- **Updates**: Docker image updates via docker-compose pull

## Common Issues & Solutions

### 1. Authentication Issues
```bash
# Session not persisting
- Check SESSION_DRIVER (redis in production, database in dev)
- Verify SANCTUM_STATEFUL_DOMAINS includes all domains
- Ensure SESSION_DOMAIN starts with dot (.listerino.com)
- Check Redis connectivity in production
```

### 2. Performance Issues
```bash
# Slow page loads
- Check for N+1 queries with Laravel Debugbar
- Verify database indexes exist
- Enable Redis caching in production
- Use eager loading: with(['relation'])
- Check Docker container resources
```

### 3. Production Docker Issues
```bash
# Container connectivity
- Use service names for inter-container communication
  DB_HOST=listerino_db (not localhost)
  REDIS_HOST=listerino_redis (not 127.0.0.1)

# Permission errors
- Ensure www-data owns application files
- Storage directories must be writable: chmod -R 775 storage

# Configuration cache issues
- Clear bootstrap cache: rm -rf bootstrap/cache/*.php
- Rebuild config: php artisan config:cache

# Container not running (502 errors)
- Check running containers: docker ps
- Start missing container: docker start <container-id>
- Use production config: docker-compose -f docker-compose.production.yml up -d
- If ContainerConfig error: restart individual containers
```

### 4. Deployment Issues
```bash
# Frontend not updating
- Ensure npm run build completes locally
- Check frontend/dist exists in deployment
- Clear browser cache or use incognito

# Database migrations fail
- Verify database credentials match Docker environment
- Check if database exists: docker exec listerino_db psql -U listerino -l
- Run migrations manually: docker exec -w /app listerino_app php artisan migrate
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

## Recent Updates (July 20, 2025)

### Place Creation and Review Workflow
- **Draft System**: All new places now start as 'draft' status regardless of user type
- **Review Process**: 
  - Places can be edited multiple times while in draft status
  - Users can submit for review when ready
  - Editing is locked for regular users when status is 'pending_review'
  - Admin/managers can still edit pending places

### UI/UX Improvements
- **Place Preview Component**: Created `PlacePreview.vue` for real-time, non-navigable preview
- **Edit Page Layout**: 
  - Split view with edit form (5/12) and preview (7/12)
  - Fixed positioning for breadcrumbs and headers
  - Toggle button to show/hide edit panel
- **Admin Navigation**: Added "Pending Approval" submenu under Places
- **MyPlaces Page**: Hide "View" button for draft/pending places

### Technical Changes
- **Model Updates**: Modified `Place.php` to enforce editing restrictions based on status
- **Controller Updates**: `PlaceController` always creates places with 'draft' status
- **Notification System**: Temporarily disabled due to table structure mismatch with Laravel's expected schema

### Known Issues
- **Notifications**: Custom notifications table doesn't match Laravel's structure
  - Need to either migrate to Laravel's standard or create custom handling
  - Currently disabled to prevent 500 errors

---

## Deployment Summary (July 21, 2025)

### Current Production Setup
- **URL**: https://listerino.com
- **Server**: DigitalOcean VPS (137.184.113.161)
- **Architecture**: Hybrid Docker/Native
  - Docker: Laravel app, PostgreSQL, Redis
  - Native: Nginx (reverse proxy with SSL)
- **Database**: Successfully migrated from local development
- **Deployment Method**: Direct file transfer via `deploy-simple.sh`

### Key Configuration Files
- **Production**: See [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md)
- **Development**: See [DEVELOPMENT_SETUP.md](./DEVELOPMENT_SETUP.md)
- **Deployment**: See [DEPLOYMENT.md](./DEPLOYMENT.md)

### Important Notes
- Frontend must be built locally before deployment (manual) or via GitHub Actions (automatic)
- Docker containers use internal networking (service names)
- All sensitive files have been cleaned from production
- SSL certificates are managed by Let's Encrypt
- Production uses `docker-compose.production.yml` configuration
- GitHub Actions handles automated deployments on push to main branch

## Common Commands

### Development Commands
```bash
# Start all development services at once (RECOMMENDED)
composer dev
# This runs Laravel server, queue worker, log viewer, and Vite concurrently

# Individual services if needed
php artisan serve              # Laravel on http://localhost:8000
npm run dev --prefix frontend  # Vite dev server on http://localhost:5173
php artisan queue:listen       # Process queued jobs
php artisan pail               # Real-time log viewer

# Database management
php artisan migrate                    # Run pending migrations
php artisan migrate:rollback          # Rollback last migration
php artisan migrate:fresh --seed      # Reset DB with seeders
php artisan db:seed                   # Run seeders only
php artisan tinker                    # Interactive PHP shell

# Code quality
./vendor/bin/pint                     # Format PHP code (Laravel Pint)
php artisan test                      # Run all tests
php artisan test --filter=FeatureName # Run specific test
php artisan test tests/Feature/Lists  # Run tests in directory

# Cache management
php artisan cache:clear               # Clear application cache
php artisan config:clear              # Clear config cache
php artisan route:clear               # Clear route cache
php artisan view:clear                # Clear compiled views
php artisan optimize:clear            # Clear all caches

# Frontend commands
cd frontend
npm install                           # Install frontend dependencies
npm run build                        # Build for production
npm run preview                      # Preview production build
cd ..
```

### Production Commands
```bash
# SSH to production
ssh root@137.184.113.161

# Docker container management
docker ps                                              # List running containers
docker-compose -f docker-compose.production.yml up -d  # Start all containers
docker-compose -f docker-compose.production.yml down   # Stop all containers
docker-compose -f docker-compose.production.yml logs   # View logs
docker stats                                           # Monitor resource usage

# Execute commands in containers
docker exec -w /app listerino_app php artisan migrate --force
docker exec -w /app listerino_app php artisan cache:clear
docker exec -w /app listerino_app php artisan about

# Database backup
docker exec listerino_db pg_dump -U listerino listerino > backup.sql

# View Laravel logs
docker exec listerino_app tail -f /app/storage/logs/laravel.log
```

### Deployment Commands
```bash
# Automated deployment (push to main branch)
git add .
git commit -m "feat: description"
git push listerino main
# Monitor at: https://github.com/lcreative777/listerino/actions

# Manual deployment
./deploy-simple.sh

# Deploy with specific environment
./deploy-simple.sh staging
./deploy-simple.sh production
```

### Testing Specific Features
```bash
# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Test with coverage
php artisan test --coverage

# Test in parallel
php artisan test --parallel

# Database testing
php artisan test --env=testing
```

### Business Claiming Setup (New Feature)
```bash
# Install required packages
composer require laravel/cashier
composer require twilio/sdk  # Optional for SMS

# Setup database
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate

# Test Stripe webhooks locally
stripe listen --forward-to localhost:8000/stripe/webhook
```

---

## Recent Updates (August 5, 2025)

### Lists Management Enhancements
- **Sections Support**: Lists now support sections/groups with structure_version 2.0
  - Drag-and-drop reordering for both sections and items
  - JSON storage structure with backward compatibility
  - Visual grouping in edit and public views
  - Database migrations: `add_sections_support_to_lists_table`, `add_section_fields_to_list_items_table`
- **Admin Interface**: New admin interface at `/admin/lists/:id/edit` similar to chains
  - Comprehensive metadata editing (name, slug, description, category, visibility, status)
  - Section management with drag-and-drop reordering
  - Featured and verified toggles
  - View and manage list ownership
- **Saved Items Integration**: 
  - Modal interface matching `/saved` page design with collections sidebar
  - Support for adding saved places, regions, and lists to lists
  - Collections/folders support for organization
  - Fixed route ordering issues causing SQL errors

### Mapbox Integration & Geospatial Features
- **Map Discovery**: New interactive map view at `/places/map`
  - Mapbox GL JS integration with clustering
  - Search and filter capabilities
  - Geolocation support with "Near Me" functionality
  - User location preferences stored in database
- **Geocoding Service**: 
  - Address autocomplete using Mapbox Geocoding API
  - Automatic geocoding of places on save
  - `GeocodingService` class for centralized geocoding
- **PostGIS Support**:
  - Spatial indexes for performance
  - Nearby places queries using ST_DWithin
  - Bounding box queries for map viewport

### UI/UX Improvements
- **List Editor Drawer**: Changed from right-sliding to LEFT-sliding, positioned below header (margin-top: 64px)
- **Add Item Options**: Reordered and simplified:
  1. Text (custom text content)
  2. Saved (from saved items)
  3. Location (place picker)
  4. Event (event details)
  - Removed "Directory Entry" option
- **Image Display**: Increased max height from 24rem (384px) to 53rem (848px) in PostItem component
- **Section Display**: Fixed empty title issue, items disappearing, and incorrect preview display

### Technical Updates
- **New Controllers**:
  - `ListItemController`: Comprehensive list item management (add, update, delete, reorder, sections)
  - `GeocodingController`: Address search and geocoding endpoints
  - `GeospatialController`: Nearby and bounds-based place queries
  - `SavedCollectionController`: Manage saved item collections
  - `UserMapSettingsController`: User map preferences
- **Database Migrations**:
  - PostGIS extension enabled
  - Sections support (structure_version, is_section, section_id)
  - Saved collections table
  - Geospatial columns and indexes for places
  - Marketing pages table for CMS
- **Frontend Components**:
  - `SavedItemsModal.vue`: Modal for selecting saved items
  - `ListSettingsDrawer.vue`: Left-sliding settings drawer
  - `ListSection.vue`: Section display component
  - `PlacesMap.vue`: Mapbox map component
  - `LocationAutocomplete.vue`: Address autocomplete input
  - Multiple composables: `useMapbox`, `useGeolocation`, `usePlacesDiscovery`, `useToast`

### API Endpoints Added
```php
// List sections management
POST   /api/lists/{list}/sections
POST   /api/lists/{list}/convert-to-sections
PUT    /api/lists/{list}/sections/{section}
DELETE /api/lists/{list}/sections/{section}
PUT    /api/lists/{list}/sections/reorder

// Saved items integration
POST   /api/lists/{list}/items/add-saved
GET    /api/saved-items/for-list-creation

// Geocoding and geospatial
GET    /api/geocoding/search
GET    /api/geospatial/nearby
GET    /api/geospatial/in-bounds

// Admin lists management
GET    /api/admin/lists/{list}
PUT    /api/admin/lists/{list}
GET    /api/admin/list-categories
```

---

## Recent Updates (July 25, 2025)

### Business Claiming & Subscription System
- **New Feature**: Complete business claiming workflow with verification
- **Database**: Added tables for claims, verification codes, documents
- **Payment**: Stripe integration with subscription tiers
- **API**: Full REST API for claiming and subscription management
- See [CLAIMING_SETUP.md](./CLAIMING_SETUP.md) for detailed setup

### Key Files for Claiming Feature
```
app/Http/Controllers/Api/ClaimController.php
app/Http/Controllers/Api/SubscriptionController.php
app/Models/Claim.php
app/Models/ClaimDocument.php
database/migrations/2025_07_24_*_claims_*.php
```

---

### GitHub Actions

The project uses GitHub Actions for automated deployment:

**Workflow Files**:
- `.github/workflows/deploy.yml` - Production deployment workflow
- Triggers on push to `main` branch or manual dispatch
- Automatically builds frontend, runs migrations, and deploys to production

**GitHub Secrets Required**:
```
SERVER_HOST      # Production server IP (137.184.113.161)
SERVER_USER      # SSH user (root)
SERVER_SSH_KEY   # Private SSH key for deployment
```

### Required Packages & Dependencies

**PHP Dependencies** (composer.json):
- Laravel 12.0
- Laravel Sanctum 4.0 (SPA authentication)
- Intervention/Image 3.11 (image processing)
- ImageKit SDK 4.0 (CDN integration)
- Doctrine DBAL 4.3 (database migrations)

**Frontend Dependencies** (frontend/package.json):
- Vue 3.4 (Composition API)
- Vue Router 4.5
- Pinia 3.0 (state management)
- Tailwind CSS 3.2
- Tiptap 2.23 (rich text editor)
- Leaflet 1.9.4 (maps)
- VueDraggable 4.1 (drag & drop)

### Testing Commands

```bash
# Run all tests
php artisan test

# Run specific test class
php artisan test --filter=UserListTest

# Run tests with coverage (requires PCOV or Xdebug)
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests  
php artisan test --testsuite=Feature
```

---

**Last Updated**: August 5, 2025
**Maintained By**: Development Team
**Version**: 3.0