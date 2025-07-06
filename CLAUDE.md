# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ListKit is a Laravel-based directory application with custom user URLs, list management, and advanced search capabilities. Built with Laravel 11, Vue.js 3, and Inertia.js.

## Development Commands

### Local Development
```bash
# Start all development services (recommended)
composer dev

# Or run services individually:
php artisan serve              # Laravel server
php artisan queue:listen       # Queue worker
php artisan pail               # Log viewer
npm run dev                    # Vite dev server
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter TestClassName
php artisan test tests/Feature/ExampleTest.php

# Run with coverage
php artisan test --coverage
```

### Build & Deployment
```bash
# Build frontend assets
npm run build

# Clear all caches
php artisan optimize:clear

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

### Code Quality
```bash
# Format code (Laravel Pint)
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse

# Fix code style
./vendor/bin/pint --repair
```

## Architecture Overview

### Backend Architecture
- **Controllers**: Located in `app/Http/Controllers/`, organized by feature area:
  - `Api/Admin/` - Admin functionality (BulkImportController, CategoryController, etc.)
  - `Api/` - Public API endpoints (DirectoryEntryController, UserListController, etc.)
  - Authentication handled by Laravel Breeze controllers in `Auth/`

- **Models**: Eloquent models in `app/Models/` with relationships:
  - `User` → has many `UserList` → has many `ListItem` → belongs to `DirectoryEntry`
  - `DirectoryEntry` → belongs to `Category` (hierarchical) and `Location`
  - `Category` supports parent-child relationships for nested categories

- **Database**: PostgreSQL with full-text search capabilities
  - Migrations use PostgreSQL-specific features (JSON columns, full-text indexes)
  - Search functionality implemented using `tsvector` columns

### Frontend Architecture
- **Inertia.js Pages**: Located in `resources/js/Pages/`, mapped to Laravel routes
  - Admin pages under `Admin/` subdirectory
  - Public pages organized by feature (Directory/, Profile/, etc.)

- **Vue Components**: Reusable components in `resources/js/Components/`
  - Form components use v-model with Inertia's form helpers
  - Rich text editing with Tiptap in `TiptapEditor.vue`
  - Map integration with Leaflet in `LeafletMap.vue`

- **State Management**: No Vuex/Pinia - state managed through Inertia props and local component state

### Key Implementation Details

1. **Custom URLs**: Users can set custom URLs handled by route wildcards:
   - Routes defined in `routes/web.php` with `/{username}/{slug?}` pattern
   - Controller logic in `ProfileController` and `UserListController`

2. **Image Handling**: 
   - Stored in `storage/app/public/` with symbolic link to `public/storage/`
   - Managed through `DirectoryEntryController` with validation

3. **Search Implementation**:
   - PostgreSQL full-text search with `search_vector` column
   - Search logic in `DirectoryEntryController::index()` and `search()`

4. **Role-Based Access**:
   - Roles: admin, manager, user
   - Middleware: `EnsureUserIsAdmin`, `EnsureUserIsManager`
   - Gate definitions in `AuthServiceProvider`

## Important Patterns

1. **API Response Format**: Controllers return Inertia responses, not JSON:
   ```php
   return Inertia::render('PageComponent', ['data' => $data]);
   ```

2. **Form Validation**: Uses Laravel Form Requests or inline validation:
   ```php
   $request->validate([
       'name' => 'required|string|max:255',
       'slug' => 'required|unique:categories,slug'
   ]);
   ```

3. **File Uploads**: Handled with Laravel's storage facade:
   ```php
   $path = $request->file('logo')->store('logos', 'public');
   ```

4. **Database Queries**: Use Eloquent relationships and scopes for complex queries
   - Avoid N+1 queries with eager loading: `with(['category', 'location'])`

## Development Guidelines

1. **Frontend Changes**: 
   - Run `npm run dev` for hot reload
   - Components auto-register via Vite glob imports
   - Use Tailwind classes for styling

2. **Backend Changes**:
   - Clear config cache after .env changes: `php artisan config:clear`
   - Run migrations after model changes: `php artisan migrate`
   - Update seeders for test data

3. **Testing**:
   - Feature tests use RefreshDatabase trait
   - Test database is in-memory SQLite
   - Factory files in `database/factories/`

4. **Deployment**:
   - Production uses PostgreSQL 15+ and Redis
   - Queue workers managed by Supervisor
   - Assets served through Nginx with CDN headers