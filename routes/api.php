<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserListController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\PublicListController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\GeocodingController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\RegionMapController;
use App\Http\Controllers\Api\Admin\RegionManagementController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Auth\SpaAuthController;
use App\Http\Controllers\Api\PageController;
use App\Services\UserProfileCacheService;

// Auth routes for SPA - these need web middleware in SPA mode for sessions
Route::post('/login', [SpaAuthController::class, 'login']);
Route::post('/register', [SpaAuthController::class, 'register']);
Route::post('/logout', [SpaAuthController::class, 'logout'])->middleware('auth');
Route::post('/forgot-password', [SpaAuthController::class, 'forgotPassword']);

// Registration status and waitlist
Route::get('/registration/status', [\App\Http\Controllers\Api\RegistrationController::class, 'status']);
Route::post('/registration/waitlist', [\App\Http\Controllers\Api\RegistrationController::class, 'joinWaitlist']);

// Public routes
Route::get('/locations/search', [LocationController::class, 'search']);
Route::get('/locations/nearby', [LocationController::class, 'nearby']);
Route::get('/locations/{location:slug}', [LocationController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

// Public user posts
Route::get('/users/{username}/posts', [PostController::class, 'userPosts']);
Route::get('/users/{username}/activity', [ProfileController::class, 'getUserActivity']);

// Tags
Route::get('/tags/search', [\App\Http\Controllers\Api\Admin\TagController::class, 'search']);

// Geospatial routes
Route::prefix('geospatial')->group(function () {
    Route::get('/nearby', [\App\Http\Controllers\Api\GeospatialController::class, 'nearby']);
    Route::get('/in-bounds', [\App\Http\Controllers\Api\GeospatialController::class, 'inBounds']);
    Route::get('/place/{place}/details', [\App\Http\Controllers\Api\GeospatialController::class, 'placeDetails']);
    Route::get('/distance', [\App\Http\Controllers\Api\GeospatialController::class, 'distance']);
    Route::get('/stats', [\App\Http\Controllers\Api\GeospatialController::class, 'stats']);
    Route::get('/needs-geocoding', [\App\Http\Controllers\Api\GeospatialController::class, 'needsGeocoding']);
    
    // POST routes for geocoding
    Route::post('/geocode', [\App\Http\Controllers\Api\GeospatialController::class, 'geocode']);
    Route::post('/reverse-geocode', [\App\Http\Controllers\Api\GeospatialController::class, 'reverseGeocode']);
    Route::post('/batch-geocode', [\App\Http\Controllers\Api\GeospatialController::class, 'batchGeocode'])->middleware('auth');
});

// Places routes (new canonical URL structure)
Route::get('/places', [\App\Http\Controllers\Api\LocationAwarePlaceController::class, 'index']);
Route::get('/places/public', [PlaceController::class, 'publicIndex']);

// Short URL redirect
Route::get('/p/{id}', [PlaceController::class, 'showById'])->where('id', '\d+');

// Place claiming route (must be before hierarchical routes)
Route::get('/places/{slug}/for-claiming', [PlaceController::class, 'showForClaiming']);

// Geospatial endpoints (must be before dynamic routes)
Route::get('/places/map-data', [PlaceController::class, 'mapData']);
Route::get('/places/search-location', [PlaceController::class, 'searchWithLocation']);

// Geocoding endpoints
Route::prefix('geocoding')->group(function () {
    Route::post('/validate', [GeocodingController::class, 'validateAddress']);
    Route::post('/geocode', [GeocodingController::class, 'geocode']);
    Route::post('/reverse', [GeocodingController::class, 'reverseGeocode']);
});

// Browse routes (hierarchical)
Route::get('/places/{state}', [\App\Http\Controllers\Api\LocationAwarePlaceController::class, 'browseByState'])->where('state', '[a-z\-]+');
Route::get('/places/{state}/{city}', [\App\Http\Controllers\Api\LocationAwarePlaceController::class, 'browseByCity'])->where(['state' => '[a-z\-]+', 'city' => '[a-z\-]+']);
Route::get('/places/{state}/{city}/{category}', [PlaceController::class, 'browseByCategory'])->where(['state' => '[a-z\-]+', 'city' => '[a-z\-]+', 'category' => '[a-z\-]+']);

// Canonical entry route (must be last and have ID constraint)
Route::get('/places/{state}/{city}/{category}/{entry}', [PlaceController::class, 'showByCanonicalUrl'])
    ->where(['state' => '[a-z\-]+', 'city' => '[a-z\-]+', 'category' => '[a-z\-]+', 'entry' => '.*-\d+$']);

// Legacy routes (for backward compatibility)
Route::get('/places/category/{slug}', [\App\Http\Controllers\Api\LocationAwarePlaceController::class, 'byCategory']);
Route::get('/places/entry/{slug}', [PlaceController::class, 'show']);
Route::get('/places/{id}', [PlaceController::class, 'showById'])->where('id', '\d+');

// Location management routes
Route::post('/places/set-location', [\App\Http\Controllers\Api\LocationAwarePlaceController::class, 'setLocation']);
Route::post('/places/clear-location', [\App\Http\Controllers\Api\LocationAwarePlaceController::class, 'clearLocation']);


// Region routes
Route::prefix('regions')->group(function () {
    Route::get('/', [RegionController::class, 'index']);
    Route::get('/search', [RegionController::class, 'search']);
    Route::get('/popular', [RegionController::class, 'popular']);
    Route::get('/nearby', [RegionController::class, 'nearby']);
    Route::get('/by-slug/{slug}', [RegionController::class, 'getBySlug']);
    Route::get('/by-slug/{state}/{city?}/{neighborhood?}', [RegionController::class, 'showBySlug']);
    Route::get('/{slug}/featured-regions', [RegionController::class, 'getFeaturedRegions']); // NEW - Get featured regions
    Route::get('/{id}', [RegionController::class, 'show']);
    Route::get('/{id}/entries', [RegionController::class, 'entries']);
    Route::get('/{id}/children', [RegionController::class, 'children']);
    Route::get('/{id}/featured', [RegionController::class, 'featured']);
});

// Region Map routes (for two-column layout with map)
Route::prefix('region-map')->group(function () {
    Route::get('/{regionId}/two-column', [RegionMapController::class, 'getTwoColumnLayout']);
    Route::get('/{regionId}/details', [RegionMapController::class, 'getRegionDetails']);
    Route::get('/{regionId}/clusters', [RegionMapController::class, 'getClusteredPlaces']);
    Route::get('/{regionId}/nearby', [RegionMapController::class, 'getNearbyRegions']);
    Route::post('/viewport-places', [RegionMapController::class, 'getMapViewportPlaces']);
});

// Local pages routes
Route::get('/local', [\App\Http\Controllers\Api\LocalController::class, 'index']);

// Marketing pages (public)
Route::get('/marketing-pages/{key}', [\App\Http\Controllers\Api\MarketingPagePublicController::class, 'show']);

// User profiles (with /up/ prefix)
Route::get('/up/@{username}', function ($username) {
    $user = \App\Models\User::where('username', $username)
        ->orWhere('custom_url', $username)
        ->first();
    
    if ($user) {
        // Use the ProfileController with cache service for full profile data
        $cacheService = app(UserProfileCacheService::class);
        return app(ProfileController::class)->showByCustomUrl($username, $cacheService);
    }
    
    abort(404);
});

// Channel profiles (with @ prefix)
Route::get('/@{slug}', function ($slug) {
    $channel = \App\Models\Channel::where('slug', $slug)->first();
    
    if ($channel) {
        return app(\App\Http\Controllers\Api\ChannelController::class)->show($slug);
    }
    
    abort(404);
});
// User lists (with /up/ prefix)
Route::get('/up/@{username}/lists', function ($username) {
    return app(UserListController::class)->userLists($username);
});
Route::get('/up/@{username}/{slug}', [UserListController::class, 'showBySlug']);

// Channel lists (with @ prefix)
Route::get('/@{slug}/lists', function ($slug) {
    $channel = \App\Models\Channel::where('slug', $slug)->first();
    
    if ($channel) {
        return app(\App\Http\Controllers\Api\ChannelController::class)->lists($channel, request());
    }
    
    abort(404);
});

// Public channel routes (for edit page and public viewing)
Route::get('/channels', [\App\Http\Controllers\Api\ChannelController::class, 'index']);
Route::get('/channels/{channel}', [\App\Http\Controllers\Api\ChannelController::class, 'show']);
Route::get('/channels/{channel}/chains', [\App\Http\Controllers\Api\ChannelController::class, 'chains']);

// Public list categories
Route::get('/list-categories/public', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'publicOptions']);

// Channel individual list - using proper controller method
Route::get('/channels/{channel:slug}/lists/{listSlug}', [\App\Http\Controllers\Api\ChannelController::class, 'showList']);

// Legacy route for backward compatibility
Route::get('/@{channelSlug}/{listSlug}', function ($channelSlug, $listSlug) {
    // Redirect to the new route structure
    return app(\App\Http\Controllers\Api\ChannelController::class)->showList(
        \App\Models\Channel::where('slug', $channelSlug)->firstOrFail(),
        $listSlug
    );
});

// Legacy routes for backward compatibility (can be removed later)
Route::get('/users/by-username/{username}', [UserProfileController::class, 'showByUsername']);
Route::get('/users/{username}/lists', [UserListController::class, 'userLists']);
Route::get('/users/{username}/{slug}', [UserListController::class, 'showBySlug']);

// Public lists (optimized)
Route::get('/lists/public', [PublicListController::class, 'index']);
Route::get('/lists/public/categories', [PublicListController::class, 'categories']);
// Legacy route
Route::get('/lists/public/legacy', [UserListController::class, 'publicLists']);

// Location detection
Route::get('/location/detect', [RegionController::class, 'detectLocation']);

// Public stats endpoint
Route::get('/stats', function () {
    try {
        return response()->json([
            'totalPlaces' => \App\Models\Place::where('status', 'published')->count(),
            'totalRegions' => \App\Models\Region::count(),
            'totalLists' => \App\Models\UserList::public()->count(),
            'totalUsers' => \App\Models\User::count(),
        ]);
    } catch (\Exception $e) {
        \Log::error('Stats endpoint error: ' . $e->getMessage());
        return response()->json([
            'totalPlaces' => 0,
            'totalRegions' => 0,
            'totalLists' => 0,
            'totalUsers' => 0,
            'error' => 'Failed to load stats'
        ], 500);
    }
});

// Route disambiguation endpoint
Route::get('/resolve-path', function (Request $request) {
    $path = $request->input('path');
    $segments = array_filter(explode('/', $path));
    
    if (empty($segments)) {
        return response()->json(['type' => 'home']);
    }
    
    // Check if it's a known static route
    $staticRoutes = ['login', 'register', 'dashboard', 'admin', 'directory', 'places', 'location'];
    if (in_array($segments[0], $staticRoutes)) {
        return response()->json(['type' => 'static', 'route' => $segments[0]]);
    }
    
    // For dynamic routes, check what exists
    if (count($segments) === 1) {
        // Could be a user or a state
        $user = \App\Models\User::where('custom_url', $segments[0])->first();
        if ($user) {
            return response()->json(['type' => 'user', 'data' => ['username' => $user->custom_url]]);
        }
        
        $region = \App\Models\Region::where('slug', $segments[0])
            ->where('level', 1)
            ->first();
        if ($region) {
            return response()->json(['type' => 'region', 'level' => 1, 'data' => ['state' => $region->slug]]);
        }
    }
    
    return response()->json(['type' => 'not_found']);
});

// Authenticated routes
// In SPA mode, we use the default 'auth' middleware which uses the web guard
Route::middleware(['auth'])->group(function () {
    // Return authenticated user info
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        if ($user) {
            // Ensure we have the full user data
            $user->loadCount(['followers', 'following', 'lists']);
            
            // Make sure name field is populated from firstname/lastname if needed
            if (!$user->name && ($user->firstname || $user->lastname)) {
                $user->name = trim($user->firstname . ' ' . $user->lastname);
            }
        }
        return $user;
    });
    
    // Home feed
    Route::get('/home', [HomeController::class, 'getFeed']);
    Route::get('/home/metadata', [HomeController::class, 'getMetadata']);
    
    // User map settings
    Route::get('/user/map-settings', [\App\Http\Controllers\Api\UserMapSettingsController::class, 'show']);
    Route::put('/user/map-settings', [\App\Http\Controllers\Api\UserMapSettingsController::class, 'update']);
    Route::delete('/user/map-settings', [\App\Http\Controllers\Api\UserMapSettingsController::class, 'destroy']);
    
    // Posts
    Route::prefix('posts')->group(function () {
        Route::post('/', [PostController::class, 'store']);
        Route::get('/{id}', [PostController::class, 'show']);
        Route::put('/{id}', [PostController::class, 'update']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
        Route::post('/{id}/tack', [PostController::class, 'tack']);
        Route::delete('/{id}/tack', [PostController::class, 'untack']);
    });
    
    // ImageKit authentication
    Route::get('/imagekit/auth', function () {
        $imageKitService = app(\App\Services\ImageKitService::class);
        return response()->json($imageKitService->getAuthenticationParams());
    });
    
    // Profile management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
        Route::post('/image', [ProfileController::class, 'updateImage']);
        Route::post('/check-url', [ProfileController::class, 'checkCustomUrl']);
    });
    
    // Notifications (existing follow notifications)
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    Route::delete('/notifications/clear-read', [\App\Http\Controllers\Api\NotificationController::class, 'clearRead']);
    
    // App Notifications (new system for messages)
    Route::get('/app-notifications', [\App\Http\Controllers\Api\AppNotificationController::class, 'index']);
    Route::get('/app-notifications/unread', [\App\Http\Controllers\Api\AppNotificationController::class, 'unread']);
    Route::post('/app-notifications/{notification}/read', [\App\Http\Controllers\Api\AppNotificationController::class, 'markAsRead']);
    Route::post('/app-notifications/read-all', [\App\Http\Controllers\Api\AppNotificationController::class, 'markAllAsRead']);
    Route::delete('/app-notifications/{notification}', [\App\Http\Controllers\Api\AppNotificationController::class, 'destroy']);
    Route::get('/app-notifications/statistics', [\App\Http\Controllers\Api\AppNotificationController::class, 'statistics']);
    
    // User's own lists
    Route::get('/lists', [UserListController::class, 'index']);
    Route::post('/lists', [UserListController::class, 'store']);
    Route::post('/lists/quick-create', [UserListController::class, 'quickCreate']); // Quick create with defaults
    Route::get('/lists/{id}', [UserListController::class, 'show']);
    Route::put('/lists/{id}', [UserListController::class, 'update']);
    Route::patch('/lists/{id}/field', [UserListController::class, 'patchField']); // Single field update for inline editing
    Route::delete('/lists/{id}', [UserListController::class, 'destroy']);
    
    // User's own places
    Route::get('/my-places', function (Request $request) {
        $user = $request->user();
        
        $places = $user->createdPlaces()
            ->with(['location', 'category', 'stateRegion', 'cityRegion'])
            ->latest()
            ->paginate(20);
            
        return response()->json($places);
    });
    
    // List items management (old - now handled by ListItemController below)
    // Route::post('/lists/{list}/items', [UserListController::class, 'addItem']);
    // Route::put('/lists/{list}/items/{item}', [UserListController::class, 'updateItem']);
    // Route::delete('/lists/{list}/items/{item}', [UserListController::class, 'removeItem']);
    // Route::put('/lists/{list}/items/reorder', [UserListController::class, 'reorderItems']);

    // List Chains (Lists of Lists)
    Route::apiResource('chains', \App\Http\Controllers\Api\ListChainController::class);
    Route::post('/chains/{chain}/reorder', [\App\Http\Controllers\Api\ListChainController::class, 'reorder']);
    
    // Search directory entries for lists
    Route::get('/directory-entries/search', [UserListController::class, 'searchEntries']);
    
    // List sharing (for private lists)
    Route::get('/lists/{list}/shares', [UserListController::class, 'getShares']);
    Route::post('/lists/{list}/shares', [UserListController::class, 'shareList']);
    Route::delete('/lists/{list}/shares/{share}', [UserListController::class, 'removeShare']);
    
    // List items management
    Route::post('/lists/{list}/items', [\App\Http\Controllers\Api\ListItemController::class, 'store']);
    // Specific routes must come before generic {item} routes
    Route::put('/lists/{list}/items/reorder', [\App\Http\Controllers\Api\ListItemController::class, 'reorder']);
    Route::post('/lists/{list}/items/add-saved', [\App\Http\Controllers\Api\ListItemController::class, 'addSavedItems']);
    // Generic item routes come last
    Route::put('/lists/{list}/items/{item}', [\App\Http\Controllers\Api\ListItemController::class, 'update']);
    Route::delete('/lists/{list}/items/{item}', [\App\Http\Controllers\Api\ListItemController::class, 'destroy']);
    
    // List sections management
    Route::post('/lists/{list}/sections', [\App\Http\Controllers\Api\ListItemController::class, 'createSection']);
    // Specific routes must come before generic {section} routes
    Route::put('/lists/{list}/sections/reorder', [\App\Http\Controllers\Api\ListItemController::class, 'reorderSections']);
    Route::post('/lists/{list}/convert-to-sections', [\App\Http\Controllers\Api\ListItemController::class, 'convertToSections']);
    // Generic section routes come last
    Route::put('/lists/{list}/sections/{section}', [\App\Http\Controllers\Api\ListItemController::class, 'updateSection']);
    Route::delete('/lists/{list}/sections/{section}', [\App\Http\Controllers\Api\ListItemController::class, 'deleteSection']);
    
    // User search for sharing
    Route::get('/users/search', [UserListController::class, 'searchUsers']);
    
    // List categories for authenticated users
    Route::get('/list-categories', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'options']);
    
    // Follow/Unfollow routes
    Route::post('/users/{user}/follow', [\App\Http\Controllers\Api\FollowController::class, 'followUser']);
    Route::delete('/users/{user}/follow', [\App\Http\Controllers\Api\FollowController::class, 'unfollowUser']);
    Route::post('/places/{place}/follow', [\App\Http\Controllers\Api\FollowController::class, 'followPlace']);
    Route::delete('/places/{place}/follow', [\App\Http\Controllers\Api\FollowController::class, 'unfollowPlace']);
    Route::get('/following', [\App\Http\Controllers\Api\FollowController::class, 'following']);
    Route::get('/users/{user}/followers', [\App\Http\Controllers\Api\FollowController::class, 'followers']);
    Route::get('/following/check', [\App\Http\Controllers\Api\FollowController::class, 'checkFollowing']);
    
    // Saved Items routes
    Route::get('/saved-items', [\App\Http\Controllers\Api\SavedItemController::class, 'index']);
    Route::post('/saved-items', [\App\Http\Controllers\Api\SavedItemController::class, 'store']);
    Route::delete('/saved-items/{type}/{id}', [\App\Http\Controllers\Api\SavedItemController::class, 'destroy']);
    Route::post('/saved-items/check', [\App\Http\Controllers\Api\SavedItemController::class, 'checkSaved']);
    Route::get('/saved-items/for-list-creation', [\App\Http\Controllers\Api\SavedItemController::class, 'forListCreation']);
    
    // Saved Collections routes
    Route::get('/saved-collections', [\App\Http\Controllers\Api\SavedCollectionController::class, 'index']);
    Route::post('/saved-collections', [\App\Http\Controllers\Api\SavedCollectionController::class, 'store']);
    Route::get('/saved-collections/{collection}', [\App\Http\Controllers\Api\SavedCollectionController::class, 'show']);
    Route::put('/saved-collections/{collection}', [\App\Http\Controllers\Api\SavedCollectionController::class, 'update']);
    Route::delete('/saved-collections/{collection}', [\App\Http\Controllers\Api\SavedCollectionController::class, 'destroy']);
    Route::post('/saved-collections/{collection}/add-items', [\App\Http\Controllers\Api\SavedCollectionController::class, 'addItems']);
    Route::post('/saved-collections/{collection}/remove-items', [\App\Http\Controllers\Api\SavedCollectionController::class, 'removeItems']);
    Route::post('/saved-collections/reorder', [\App\Http\Controllers\Api\SavedCollectionController::class, 'reorder']);
    Route::put('/saved-items/{item}/move', [\App\Http\Controllers\Api\SavedCollectionController::class, 'moveItem']);
    
    // Channel routes (authenticated only)
    Route::post('/channels', [\App\Http\Controllers\Api\ChannelController::class, 'store']);
    Route::put('/channels/{channel}', [\App\Http\Controllers\Api\ChannelController::class, 'update']);
    Route::delete('/channels/{channel}', [\App\Http\Controllers\Api\ChannelController::class, 'destroy']);
    Route::post('/channels/{channel}/follow', [\App\Http\Controllers\Api\ChannelController::class, 'follow']);
    Route::delete('/channels/{channel}/follow', [\App\Http\Controllers\Api\ChannelController::class, 'unfollow']);
    Route::get('/channels/{channel}/followers', [\App\Http\Controllers\Api\ChannelController::class, 'followers']);
    Route::get('/channels/{channel}/lists', [\App\Http\Controllers\Api\ChannelController::class, 'lists']);
    Route::post('/channels/check-slug', [\App\Http\Controllers\Api\ChannelController::class, 'checkSlug']);
    Route::get('/my-channels', [\App\Http\Controllers\Api\ChannelController::class, 'myChannels']);
    Route::get('/followed-channels', [\App\Http\Controllers\Api\ChannelController::class, 'followedChannels']);
    
    // User's channels
    Route::get('/users/{user}/channels', function ($userId) {
        $user = \App\Models\User::findOrFail($userId);
        return $user->channels()
            ->withCount(['lists', 'followers'])
            ->orderBy('created_at', 'desc')
            ->get();
    });
    Route::get('/users/{user}/followed-channels', function ($userId) {
        $user = \App\Models\User::findOrFail($userId);
        return $user->followedChannels()
            ->with(['user:id,name,username,custom_url,avatar,avatar_cloudflare_id'])
            ->withCount(['lists', 'followers'])
            ->paginate(12);
    });

    // Tag validation
    Route::post('/validate-tag', function (Request $request) {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        
        $isValid = \App\Services\ProfanityFilterService::validateTag($request->name);
        
        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Tag is valid' : 'Tag contains inappropriate content'
        ]);
    });

    // Create a new place (any logged‑in user)
    Route::post('/places', [PlaceController::class, 'store']);

    // Image upload routes for places
    Route::post('/places/upload-image', [PlaceController::class, 'uploadImage']);
    Route::delete('/places/delete-image', [PlaceController::class, 'deleteImage']);

    // New Cloudflare Images routes
    Route::prefix('images')->group(function () {
        Route::post('/generate-upload-url', [\App\Http\Controllers\ImageUploadController::class, 'generateUploadUrl']);
        Route::post('/confirm-upload', [\App\Http\Controllers\ImageUploadController::class, 'confirmUpload']);
        Route::get('/test-cloudflare-connection', [\App\Http\Controllers\ImageUploadController::class, 'testCloudflareConnection']);
        Route::post('/upload', [\App\Http\Controllers\ImageUploadController::class, 'upload']);
        Route::post('/upload-async', [\App\Http\Controllers\ImageUploadController::class, 'uploadAsync']);
        Route::get('/status/{uploadId}', [\App\Http\Controllers\ImageUploadController::class, 'status']);
        Route::get('/', [\App\Http\Controllers\ImageUploadController::class, 'index']);
        Route::get('/{imageId}', [\App\Http\Controllers\ImageUploadController::class, 'show']);
        Route::delete('/{imageId}', [\App\Http\Controllers\ImageUploadController::class, 'delete']);
    });

    // Cloudflare Images routes for web session auth
    Route::prefix('cloudflare')->group(function () {
        Route::post('/upload-url', [App\Http\Controllers\Api\CloudflareImageController::class, 'generateUploadUrl']);
        Route::post('/confirm-upload', [App\Http\Controllers\Api\CloudflareImageController::class, 'confirmUpload']);
        Route::post('/update-tracking', [App\Http\Controllers\Api\CloudflareImageController::class, 'updateTracking']);
        Route::get('/image/{imageId}', [App\Http\Controllers\Api\CloudflareImageController::class, 'getImageInfo']);
        Route::delete('/image/{imageId}', [App\Http\Controllers\Api\CloudflareImageController::class, 'deleteImage']);
        Route::get('/stats', [App\Http\Controllers\Api\CloudflareImageController::class, 'getStats']);
    });

    // Media management routes
    Route::prefix('media')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\MediaController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\MediaController::class, 'store']);
        Route::get('/statistics', [App\Http\Controllers\Api\MediaController::class, 'statistics']);
        Route::get('/{media}', [App\Http\Controllers\Api\MediaController::class, 'show']);
        Route::put('/{media}', [App\Http\Controllers\Api\MediaController::class, 'update']);
        Route::delete('/{media}', [App\Http\Controllers\Api\MediaController::class, 'destroy']);
        Route::post('/bulk-delete', [App\Http\Controllers\Api\MediaController::class, 'bulkDelete']);
    });
    
    // Entity media routes
    Route::get('/entities/{entityType}/{entityId}/media', [\App\Http\Controllers\Api\EntityMediaController::class, 'getEntityMedia']);
    Route::post('/entities/{entityType}/{entityId}/media/clear-cache', [\App\Http\Controllers\Api\EntityMediaController::class, 'clearEntityMediaCache']);

    // My places route (authenticated users)
    Route::get('/my-places', [PlaceController::class, 'myPlaces']);
    
    // Update a place (authenticated users can update their own places)
    Route::put('/places/{place}', [PlaceController::class, 'update']);
    Route::patch('/places/{place}/submit-for-review', [PlaceController::class, 'submitForReview']);

    // Delete or publish a place, or view places pending review (admin or manager)
    Route::middleware('role.api:admin,manager')->group(function () {
        Route::delete('/places/{entry}', [PlaceController::class, 'destroy']);
        Route::post('/places/{entry}/publish', [PlaceController::class, 'publish']);
        Route::get('/places/pending-review', [PlaceController::class, 'pendingReview']);
    });

    // Business claiming routes
    Route::get('/places/{place}/claim-status', [ClaimController::class, 'checkClaimability']); // Direct route for claim status
    Route::post('/places/{place}/claim', [ClaimController::class, 'initiateClaim']); // Direct route for initiating claim
    
    Route::prefix('claims')->group(function () {
        Route::get('/places/{place}/check', [ClaimController::class, 'checkClaimability']);
        Route::post('/places/{place}/initiate', [ClaimController::class, 'initiateClaim']);
        Route::get('/{claim}/status', [ClaimController::class, 'getClaimStatus']);
        Route::post('/{claim}/documents', [ClaimController::class, 'uploadDocument']);
        Route::post('/{claim}/verify', [\App\Http\Controllers\Api\VerificationController::class, 'verifyCode']);
        Route::post('/{claim}/resend-code', [\App\Http\Controllers\Api\VerificationController::class, 'resendCode']);
        Route::put('/{claim}/update-tier', [ClaimController::class, 'updateTier']);
        Route::post('/{claim}/create-payment-intent', [ClaimController::class, 'createPaymentIntent']);
        Route::post('/{claim}/confirm-payment', [ClaimController::class, 'confirmPayment']);
        Route::post('/{claim}/create-verification-fee-payment', [ClaimController::class, 'createVerificationFeePayment']);
        Route::post('/{claim}/confirm-verification-fee', [ClaimController::class, 'confirmVerificationFeePayment']);
        Route::post('/{claim}/create-combined-payment', [ClaimController::class, 'createCombinedPayment']);
        Route::delete('/{claim}', [ClaimController::class, 'cancelClaim']);
        Route::get('/my-claims', [ClaimController::class, 'getUserClaims']);
        
        // Test mode endpoints
        Route::post('/{claim}/test-approve', [ClaimController::class, 'testApprove']);
        Route::get('/{claim}/debug-code', [ClaimController::class, 'debugCode']);
    });

    // Subscription routes
    Route::prefix('subscriptions')->group(function () {
        Route::get('/plans', [\App\Http\Controllers\Api\SubscriptionController::class, 'getPlans']);
        Route::get('/places/{place}/status', [\App\Http\Controllers\Api\SubscriptionController::class, 'getSubscriptionStatus']);
        Route::post('/places/{place}/subscribe', [\App\Http\Controllers\Api\SubscriptionController::class, 'subscribe']);
        Route::post('/places/{place}/cancel', [\App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
        Route::post('/places/{place}/resume', [\App\Http\Controllers\Api\SubscriptionController::class, 'resume']);
    });

    // Admin routes - Fixed to match what Vue components expect
    Route::prefix('admin')->middleware('role.api:admin,manager')->group(function () {
        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        
        // User management - THESE are the correct paths the Vue components use
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::get('/users/stats', [UserManagementController::class, 'stats']);
        Route::get('/users/{user}', [UserManagementController::class, 'show']);
        Route::put('/users/{user}', [UserManagementController::class, 'update']);
        Route::put('/users/{user}/password', [UserManagementController::class, 'updatePassword']);
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
        Route::post('/users/bulk-update', [UserManagementController::class, 'bulkUpdate']);
        
        // Placeholder for entries endpoint used by dashboard
        Route::get('/entries', function (Request $request) {
            return response()->json(['data' => []]);
        });
        
        // Bulk import routes
        Route::post('/bulk-import/csv', [App\Http\Controllers\Api\Admin\BulkImportController::class, 'uploadCsv']);
        Route::get('/bulk-import/template', [App\Http\Controllers\Api\Admin\BulkImportController::class, 'downloadTemplate']);
        
        // Region management
        Route::prefix('regions')->group(function () {
            Route::get('/', [RegionManagementController::class, 'index']);
            Route::get('/stats', [RegionManagementController::class, 'stats']);
            Route::get('/duplicates', [RegionManagementController::class, 'duplicates']);
            Route::post('/merge-duplicates', [RegionManagementController::class, 'mergeDuplicates']);
            Route::post('/', [RegionManagementController::class, 'store']);
            Route::post('/bulk-import', [\App\Http\Controllers\Api\Admin\RegionBulkImportController::class, 'import']);
            Route::get('/bulk-import/template', [\App\Http\Controllers\Api\Admin\RegionBulkImportController::class, 'downloadTemplate']);
            Route::get('/{id}', [RegionManagementController::class, 'show']);
            Route::put('/{id}', [RegionManagementController::class, 'update']);
            Route::delete('/{id}', [RegionManagementController::class, 'destroy']);
            Route::post('/{id}/featured', [RegionManagementController::class, 'manageFeatured']);
            Route::post('/{id}/assign-entries', [RegionManagementController::class, 'assignEntries']);
            Route::get('/{id}/places', [RegionManagementController::class, 'places']);
            Route::delete('/{regionId}/places/{placeId}', [RegionManagementController::class, 'removePlace']);
            
            // Featured places management
            Route::post('/{region}/featured-places', [RegionManagementController::class, 'addFeaturedPlace']);
            Route::put('/{region}/featured-places/{place}', [RegionManagementController::class, 'updateFeaturedPlace']);
            Route::delete('/{region}/featured-entries/{place}', [RegionManagementController::class, 'removeFeaturedPlace']);
            
            // Featured lists management
            Route::post('/{region}/featured-lists', [RegionManagementController::class, 'addFeaturedList']);
            Route::delete('/{region}/featured-lists/{list}', [RegionManagementController::class, 'removeFeaturedList']);
            
            // Featured regions management (NEW)
            Route::get('/{region}/featured-regions', [RegionManagementController::class, 'getFeaturedRegions']);
            Route::post('/{region}/featured-regions', [RegionManagementController::class, 'addFeaturedRegion']);
            Route::put('/{region}/featured-regions/{featuredRegion}', [RegionManagementController::class, 'updateFeaturedRegion']);
            Route::delete('/{region}/featured-regions/{featuredRegion}', [RegionManagementController::class, 'removeFeaturedRegion']);
            Route::put('/{region}/featured-regions/order', [RegionManagementController::class, 'updateFeaturedRegionsOrder']);
            Route::get('/{region}/search-regions-to-feature', [RegionManagementController::class, 'searchRegionsToFeature']);
        });
        
        // Place management routes
        
        // Bulk data import/export
        Route::prefix('bulk-data')->group(function () {
            Route::get('/export/places', [App\Http\Controllers\Api\Admin\BulkDataController::class, 'exportPlaces']);
            Route::get('/export/regions', [App\Http\Controllers\Api\Admin\BulkDataController::class, 'exportRegions']);
            Route::post('/import/places', [App\Http\Controllers\Api\Admin\BulkDataController::class, 'importPlaces']);
            Route::post('/import/regions', [App\Http\Controllers\Api\Admin\BulkDataController::class, 'importRegions']);
            Route::get('/template/places', [App\Http\Controllers\Api\Admin\BulkDataController::class, 'getPlacesTemplate']);
            Route::get('/template/regions', [App\Http\Controllers\Api\Admin\BulkDataController::class, 'getRegionsTemplate']);
        });
        Route::get('/places', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'index']);
        Route::get('/places/stats', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'stats']);
        Route::get('/places/pending', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'pending']);
        Route::post('/places', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'store']);
        Route::get('/places/{place}', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'show']);
        Route::put('/places/{place}', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'update']);
        Route::delete('/places/{place}', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'destroy']);
        Route::post('/places/{place}/approve', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'approve']);
        Route::post('/places/{place}/reject', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'reject']);
        Route::post('/places/bulk-update', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'bulkUpdate']);
        Route::post('/places/update-canonical-urls', [\App\Http\Controllers\Api\Admin\PlaceController::class, 'updateCanonicalUrls']);
        
        // Place categories management
        Route::get('/categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index']);
        Route::get('/categories/stats', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'stats']);
        Route::post('/categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'store']);
        Route::get('/categories/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'show']);
        Route::put('/categories/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'destroy']);
        
        // List management routes
        Route::get('/lists', [\App\Http\Controllers\Api\Admin\ListManagementController::class, 'index']);
        Route::get('/lists/stats', [\App\Http\Controllers\Api\Admin\ListManagementController::class, 'stats']);
        Route::get('/lists/{list}', [\App\Http\Controllers\Api\Admin\ListManagementController::class, 'show']);
        Route::put('/lists/{list}', [\App\Http\Controllers\Api\Admin\ListManagementController::class, 'update']);
        Route::delete('/lists/{list}', [\App\Http\Controllers\Api\Admin\ListManagementController::class, 'destroy']);
        Route::post('/lists/{list}/status', [\App\Http\Controllers\Api\Admin\ListManagementController::class, 'updateStatus']);
        Route::post('/lists/bulk-update', [\App\Http\Controllers\Api\Admin\ListManagementController::class, 'bulkUpdate']);
        
        // List categories for admin
        Route::get('/list-categories', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'index']);
        
        // Media management routes - Using new MediaManagementController
        Route::get('/media', [\App\Http\Controllers\Api\Admin\MediaManagementController::class, 'index']);
        Route::get('/media/sync-cloudflare', [\App\Http\Controllers\Api\Admin\MediaManagementController::class, 'syncWithCloudflare']);
        Route::post('/media/import-cloudflare', [\App\Http\Controllers\Api\Admin\MediaManagementController::class, 'importFromCloudflare']);
        Route::post('/media/bulk-import', [\App\Http\Controllers\Api\Admin\MediaManagementController::class, 'bulkImport']);
        Route::delete('/media/{id}', [\App\Http\Controllers\Api\Admin\MediaManagementController::class, 'destroy']);
        Route::get('/media/stats', [\App\Http\Controllers\Api\Admin\MediaController::class, 'stats']);
        Route::post('/media/bulk-delete', [\App\Http\Controllers\Api\Admin\MediaController::class, 'bulkDelete']);
        Route::post('/media/cleanup', [\App\Http\Controllers\Api\Admin\MediaController::class, 'cleanup']);
        
        // Tags management routes
        Route::get('/tags', [\App\Http\Controllers\Api\Admin\TagController::class, 'index']);
        Route::get('/tags/stats', [\App\Http\Controllers\Api\Admin\TagController::class, 'stats']);
        Route::post('/tags', [\App\Http\Controllers\Api\Admin\TagController::class, 'store']);
        Route::get('/tags/{tag}', [\App\Http\Controllers\Api\Admin\TagController::class, 'show']);
        Route::put('/tags/{tag}', [\App\Http\Controllers\Api\Admin\TagController::class, 'update']);
        Route::delete('/tags/{tag}', [\App\Http\Controllers\Api\Admin\TagController::class, 'destroy']);
        Route::post('/tags/bulk-update', [\App\Http\Controllers\Api\Admin\TagController::class, 'bulkUpdate']);
        
        // Add data routes to match admin.php structure
        Route::prefix('data')->group(function () {
            // List categories management
            Route::get('/list-categories', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'index']);
            Route::get('/list-categories/stats', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'stats']);
            Route::get('/list-categories/options', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'options']);
            Route::post('/list-categories', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'store']);
            Route::get('/list-categories/{category}', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'show']);
            Route::put('/list-categories/{category}', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'update']);
            Route::delete('/list-categories/{category}', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'destroy']);
            Route::post('/list-categories/bulk-update', [\App\Http\Controllers\Api\Admin\ListCategoryController::class, 'bulkUpdate']);
        });
        
        // Featured Places Management
        Route::prefix('featured-places')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\FeaturedPlacesController::class, 'index']);
            Route::get('/search', [\App\Http\Controllers\Api\Admin\FeaturedPlacesController::class, 'search']);
            Route::post('/', [\App\Http\Controllers\Api\Admin\FeaturedPlacesController::class, 'store']);
            Route::delete('/', [\App\Http\Controllers\Api\Admin\FeaturedPlacesController::class, 'destroy']);
            Route::post('/update-order', [\App\Http\Controllers\Api\Admin\FeaturedPlacesController::class, 'updateOrder']);
            Route::post('/metadata', [\App\Http\Controllers\Api\Admin\FeaturedPlacesController::class, 'updateMetadata']);
        });
        
        // Curated Lists Management
        Route::prefix('curated-lists')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'destroy']);
            Route::post('/{id}/add-place', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'addPlace']);
            Route::delete('/{id}/remove-place/{placeId}', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'removePlace']);
            Route::put('/{id}/reorder-places', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'reorderPlaces']);
        });
        
        // Get curated lists for specific regions/categories
        Route::get('/regions/{regionId}/curated-lists', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'forRegion']);
        Route::get('/categories/{categoryId}/curated-lists', [\App\Http\Controllers\Api\Admin\CuratedListController::class, 'forCategory']);
        
        // Marketing Pages routes
        Route::prefix('marketing-pages')->group(function () {
            // Pages CRUD
            Route::get('/', [\App\Http\Controllers\Api\Admin\PageController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Api\Admin\PageController::class, 'stats']);
            Route::post('/', [\App\Http\Controllers\Api\Admin\PageController::class, 'store']);
            Route::get('/{page}', [\App\Http\Controllers\Api\Admin\PageController::class, 'show']);
            Route::put('/{page}', [\App\Http\Controllers\Api\Admin\PageController::class, 'update']);
            Route::delete('/{page}', [\App\Http\Controllers\Api\Admin\PageController::class, 'destroy']);
            
            // Marketing pages by key
            Route::get('/key/{key}', [\App\Http\Controllers\Api\Admin\MarketingPageController::class, 'getByKey']);
            Route::put('/key/{key}', [\App\Http\Controllers\Api\Admin\MarketingPageController::class, 'updateByKey']);
            
            // Special pages
            Route::prefix('special')->group(function () {
                // Login page settings
                Route::get('/login', [\App\Http\Controllers\Api\Admin\LoginPageController::class, 'show']);
                Route::post('/login', [\App\Http\Controllers\Api\Admin\LoginPageController::class, 'update']);
                
                // Home page settings
                Route::get('/home', [\App\Http\Controllers\Api\Admin\HomePageController::class, 'show']);
                Route::post('/home', [\App\Http\Controllers\Api\Admin\HomePageController::class, 'update']);
                Route::get('/home/places', [\App\Http\Controllers\Api\Admin\HomePageController::class, 'getPlaces']);
                
                // Local pages settings
                Route::get('/local', [\App\Http\Controllers\Api\Admin\LocalPageController::class, 'show']);
                Route::post('/local', [\App\Http\Controllers\Api\Admin\LocalPageController::class, 'update']);
                Route::get('/local/search-lists', [\App\Http\Controllers\Api\Admin\LocalPageController::class, 'searchLists']);
                Route::get('/local/search-places', [\App\Http\Controllers\Api\Admin\LocalPageController::class, 'searchPlaces']);
            });
        });
        
        // Local page settings management
        Route::prefix('local-pages')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\LocalPageController::class, 'index']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\LocalPageController::class, 'destroy']);
        });
        
        // Claim management routes (admin)
        Route::prefix('claims')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'index']);
            Route::get('/statistics', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'statistics']);
            Route::get('/{claim}', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'show']);
            Route::post('/{claim}/approve', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'approve']);
            Route::post('/{claim}/reject', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'reject']);
            Route::post('/{claim}/unclaim', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'unclaim']);
            Route::get('/{claim}/payment-details', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'paymentDetails']);
            Route::post('/documents/{document}/verify', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'verifyDocument']);
            Route::get('/documents/{document}/download', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'downloadDocument']);
        });
        
        // Claimed places dashboard route
        Route::get('/claimed-places', [\App\Http\Controllers\Api\Admin\ClaimController::class, 'index']);
        
        // Admin Notification Management
        Route::prefix('notifications')->group(function () {
            Route::get('/filters', [\App\Http\Controllers\Api\Admin\AdminNotificationController::class, 'getFilters']);
            Route::post('/send', [\App\Http\Controllers\Api\Admin\AdminNotificationController::class, 'send']);
            Route::post('/preview-recipients', [\App\Http\Controllers\Api\Admin\AdminNotificationController::class, 'previewRecipients']);
            Route::get('/statistics', [\App\Http\Controllers\Api\Admin\AdminNotificationController::class, 'statistics']);
        });
        
        // Site Settings routes
        Route::prefix('settings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'update']);
            Route::get('/{key}', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'show']);
            Route::post('/reset', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'reset']);
        });
        
        // Waitlist management routes
        Route::prefix('waitlist')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\WaitlistController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Api\Admin\WaitlistController::class, 'stats']);
            Route::post('/{id}/invite', [\App\Http\Controllers\Api\Admin\WaitlistController::class, 'invite']);
            Route::post('/invite-bulk', [\App\Http\Controllers\Api\Admin\WaitlistController::class, 'inviteBulk']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\WaitlistController::class, 'destroy']);
        });
    });
    
    // Social Interactions Routes
    // Likes
    Route::prefix('likes')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\LikeController::class, 'index']);
        Route::post('/toggle', [\App\Http\Controllers\Api\LikeController::class, 'toggle']);
        Route::get('/check', [\App\Http\Controllers\Api\LikeController::class, 'check']);
        Route::get('/my-likes', [\App\Http\Controllers\Api\LikeController::class, 'userLikes']);
    });
    
    // Comments
    Route::prefix('comments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CommentController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\CommentController::class, 'store']);
        Route::put('/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'update']);
        Route::delete('/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'destroy']);
        Route::get('/{comment}/replies', [\App\Http\Controllers\Api\CommentController::class, 'replies']);
    });
    
    // Reposts
    Route::prefix('reposts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\RepostController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\RepostController::class, 'store']);
        Route::delete('/', [\App\Http\Controllers\Api\RepostController::class, 'destroy']);
        Route::get('/check', [\App\Http\Controllers\Api\RepostController::class, 'check']);
        Route::get('/my-reposts', [\App\Http\Controllers\Api\RepostController::class, 'userReposts']);
    });
});

// Public login page settings (needed for login page customization)
Route::get('/login-settings', [\App\Http\Controllers\Api\Admin\LoginPageController::class, 'show']);

// Public marketing/info page route (at root level)
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '^(?!@|api|admin|places|lists|regions|login|register|logout|dashboard|profile|settings|images|cloudflare|stats|resolve-path).*');