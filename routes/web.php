<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\DirectoryEntryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ListManagementController;
use App\Http\Controllers\Api\Admin\ListCategoryController;
use App\Http\Controllers\Api\Admin\TagController;
use App\Http\Controllers\Api\UserListController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    // Fetch recent public lists (only searchable ones for homepage)
    $publicLists = \App\Models\UserList::searchable()
        ->with(['user', 'items.directoryEntry'])
        ->latest()
        ->limit(6)
        ->get();
    
    // Fetch featured directory entries
    $featuredEntries = \App\Models\DirectoryEntry::where('is_featured', true)
        ->where('status', 'published')
        ->with(['category.parent', 'location'])
        ->latest()
        ->limit(6)
        ->get();
    
    // Fetch categories with counts (including subcategories)
    $categories = \App\Models\Category::whereNull('parent_id')
        ->with('children')
        ->orderBy('order_index')
        ->limit(8)
        ->get()
        ->map(function ($category) {
            $category->total_entries_count = $category->total_entries_count;
            return $category;
        });
    
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'publicLists' => $publicLists,
        'featuredEntries' => $featuredEntries,
        'categories' => $categories,
    ]);
})->name('welcome');

// Test route for drag-and-drop uploader (requires auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/test/uploader', function () {
        return Inertia::render('Test/ImageUploader');
    })->name('test.uploader');
});

// Public places browsing routes
Route::get('/places', function () {
    if (auth()->check()) {
        // Authenticated user - full browsing experience
        $query = \App\Models\DirectoryEntry::where('status', 'published')
            ->with(['category.parent', 'location']);
        
        // Apply search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Apply category filter
        if (request('category')) {
            $category = \App\Models\Category::where('slug', request('category'))->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }
        
        // Apply location filters
        if (request('state')) {
            $query->whereHas('location', function($q) {
                $q->where('state', request('state'));
            });
        }
        
        if (request('city')) {
            $query->whereHas('location', function($q) {
                $q->where('city', request('city'));
            });
        }
        
        if (request('neighborhood')) {
            $query->whereHas('location', function($q) {
                $q->where('neighborhood', request('neighborhood'));
            });
        }
        
        // Apply sorting
        switch (request('sort', 'latest')) {
            case 'name':
                $query->orderBy('title', 'asc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->latest();
                break;
            default:
                $query->latest();
        }
        
        $entries = $query->paginate(12)->appends(request()->query());
        
        $categories = \App\Models\Category::whereNull('parent_id')
            ->withCount('directoryEntries')
            ->orderBy('order_index')
            ->get();
        
        // Get available location data for filters
        $locationData = [];
        
        // Get states that have entries
        $states = \App\Models\Location::whereHas('directoryEntry', function($q) {
            $q->where('status', 'published');
        })
        ->distinct()
        ->pluck('state')
        ->filter()
        ->sort()
        ->values();
        
        // Convert state codes to full state data
        $locationData['states'] = collect(\App\Models\Location::select('state')
            ->whereHas('directoryEntry', function($q) {
                $q->where('status', 'published');
            })
            ->distinct()
            ->pluck('state'))
            ->map(function($stateCode) {
                // Simple state name mapping for now
                $usStates = [
                    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                    'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                    'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
                    'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
                    'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                    'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
                    'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
                    'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
                    'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
                    'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                    'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
                    'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
                    'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia'
                ];
                return ['code' => $stateCode, 'name' => $usStates[$stateCode] ?? $stateCode];
            })
            ->filter()
            ->values();
        
        // Get cities for selected state
        if (request('state')) {
            $locationData['cities'] = [
                request('state') => \App\Models\Location::where('state', request('state'))
                    ->whereHas('directoryEntry', function($q) {
                        $q->where('status', 'published');
                    })
                    ->distinct()
                    ->pluck('city')
                    ->filter()
                    ->sort()
                    ->values()
            ];
        }
        
        // Get neighborhoods for selected city
        if (request('city')) {
            $locationData['neighborhoods'] = [
                request('city') => \App\Models\Location::where('city', request('city'))
                    ->where('state', request('state'))
                    ->whereHas('directoryEntry', function($q) {
                        $q->where('status', 'published');
                    })
                    ->whereNotNull('neighborhood')
                    ->distinct()
                    ->pluck('neighborhood')
                    ->filter()
                    ->sort()
                    ->values()
            ];
        }
        
        return Inertia::render('Places/AuthenticatedIndex', [
            'entries' => $entries,
            'categories' => $categories,
            'filters' => request()->only(['search', 'category', 'sort', 'state', 'city', 'neighborhood']),
            'locationData' => $locationData,
        ]);
    } else {
        // Public user - curated marketing experience
        $featuredEntries = \App\Models\DirectoryEntry::where('status', 'published')
            ->where('is_featured', true)
            ->with(['category.parent', 'location'])
            ->latest()
            ->limit(6)
            ->get();
        
        $topCategories = \App\Models\Category::whereNull('parent_id')
            ->withCount('directoryEntries')
            ->orderBy('directory_entries_count', 'desc')
            ->orderBy('order_index')
            ->limit(8)
            ->get();
        
        return Inertia::render('Places/PublicIndex', [
            'featuredEntries' => $featuredEntries,
            'topCategories' => $topCategories,
            'canRegister' => Route::has('register'),
        ]);
    }
})->name('places.index');

Route::get('/places/category/{category:slug}', function (\App\Models\Category $category) {
    // Load parent relationship for the category
    $category->load('parent');
    
    // Get entries from this category and all its subcategories
    $entries = $category->getAllDirectoryEntries()
        ->where('status', 'published')
        ->with(['category.parent', 'location'])
        ->latest()
        ->paginate(12);
    
    return Inertia::render('Places/Category', [
        'category' => $category,
        'entries' => $entries,
    ]);
})->name('places.category');

// Public lists browsing routes
Route::get('/lists', function () {
    if (auth()->check()) {
        // Authenticated user - full browsing experience
        $query = \App\Models\UserList::searchable()
            ->with(['user', 'category', 'tags'])
            ->withCount('items');
        
        // Apply search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Apply category filter
        if (request('category')) {
            $category = \App\Models\ListCategory::where('slug', request('category'))->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }
        
        // Apply sorting
        switch (request('sort', 'latest')) {
            case 'views':
                $query->orderBy('views_count', 'desc');
                break;
            case 'items':
                $query->orderBy('items_count', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->latest();
        }
        
        $lists = $query->paginate(12)->appends(request()->query());
        
        $categories = \App\Models\ListCategory::where('is_active', true)
            ->withCount(['lists' => function($query) {
                $query->searchable();
            }])
            ->orderBy('sort_order')
            ->get();
        
        return Inertia::render('Lists/AuthenticatedIndex', [
            'lists' => $lists,
            'categories' => $categories,
            'filters' => request()->only(['search', 'category', 'sort']),
        ]);
    } else {
        // Public user - curated marketing experience
        $featuredLists = \App\Models\UserList::searchable()
            ->where('is_featured', true)
            ->with(['user', 'category', 'tags'])
            ->withCount('items')
            ->latest()
            ->limit(6)
            ->get();
        
        $topCategories = \App\Models\ListCategory::where('is_active', true)
            ->withCount(['lists' => function($query) {
                $query->searchable();
            }])
            ->orderBy('lists_count', 'desc')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();
        
        return Inertia::render('Lists/PublicIndex', [
            'featuredLists' => $featuredLists,
            'topCategories' => $topCategories,
            'canRegister' => Route::has('register'),
        ]);
    }
})->name('lists.public.index');

Route::get('/lists/category/{category:slug}', function (\App\Models\ListCategory $category) {
    $query = \App\Models\UserList::searchable()
        ->where('category_id', $category->id)
        ->with(['user', 'category', 'tags'])
        ->withCount('items');
    
    // Apply search filter
    if (request('search')) {
        $search = request('search');
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
    
    // Apply sorting
    switch (request('sort', 'latest')) {
        case 'views':
            $query->orderBy('views_count', 'desc');
            break;
        case 'items':
            $query->orderBy('items_count', 'desc');
            break;
        case 'name':
            $query->orderBy('name', 'asc');
            break;
        default:
            $query->latest();
    }
    
    $lists = $query->paginate(12)->appends(request()->query());
    
    return Inertia::render('Lists/PublicCategory', [
        'category' => $category,
        'lists' => $lists,
        'filters' => request()->only(['search', 'sort']),
    ]);
})->name('lists.public.category');

// Fallback route for old entry URLs - show or redirect
Route::get('/places/entry/{entry:slug}', function (\App\Models\DirectoryEntry $entry) {
    // If entry doesn't have a category or category doesn't have parent, show it directly
    if (!$entry->category_id || !$entry->category || !$entry->category->parent_id) {
        $entry->load(['category', 'location', 'owner', 'createdBy']);
        
        // Get related entries
        $relatedEntries = \App\Models\DirectoryEntry::where('status', 'published')
            ->where('id', '!=', $entry->id)
            ->when($entry->category_id, function($query) use ($entry) {
                return $query->where('category_id', $entry->category_id);
            })
            ->with(['category', 'location'])
            ->limit(4)
            ->get();
        
        return Inertia::render('Places/Show', [
            'entry' => $entry,
            'relatedEntries' => $relatedEntries,
            'parentCategory' => null,
            'childCategory' => $entry->category,
        ]);
    }
    
    // Otherwise redirect to proper URL
    return redirect($entry->getUrl(), 301);
});

// Test Direct Cloudflare upload (public for testing)
Route::get('/test-direct-upload', function () {
    return Inertia::render('TestDirectUpload');
})->name('test.direct.upload');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    // Home page (new default after login)
    Route::get('/home', function () {
        $user = auth()->user();
        
        // Load user stats
        $user->loadCount(['followers', 'following', 'lists']);
        
        // Get latest lists from followed users
        $followingIds = $user->following()->pluck('users.id')->toArray();
        
        // Include user's own lists and followed users' lists
        $userIds = array_merge([$user->id], $followingIds);
        
        // Get lists (take 5 per page to make room for places)
        $lists = \App\Models\UserList::whereIn('user_id', $userIds)
            ->searchable()
            ->with(['user', 'category', 'tags'])
            ->withCount('items')
            ->latest()
            ->take(5)
            ->get();
            
        // Get nearby directory entries (take 5 per page)
        $places = \App\Models\DirectoryEntry::where('status', 'published')
            ->with(['category.parent', 'location'])
            ->latest()
            ->take(5)
            ->get();
            
        // Add type identifier to each item
        $lists->each(function ($list) {
            $list->feed_type = 'list';
            $list->feed_timestamp = $list->created_at;
        });
        
        $places->each(function ($place) {
            $place->feed_type = 'place';
            $place->feed_timestamp = $place->created_at;
        });
        
        // Merge and sort by timestamp
        $feedItems = collect([$lists, $places])
            ->flatten()
            ->sortByDesc('feed_timestamp')
            ->values();
            
        // Create pagination-like structure
        $page = request()->get('page', 1);
        $perPage = 10;
        $total = $feedItems->count();
        
        $latestLists = new \Illuminate\Pagination\LengthAwarePaginator(
            $feedItems->forPage($page, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
            
        // Get categories for sidebar
        $categories = \App\Models\ListCategory::where('is_active', true)
            ->withCount(['lists' => function($query) {
                $query->searchable();
            }])
            ->orderBy('sort_order')
            ->limit(10)
            ->get();
            
        // Get trending tags
        $trendingTags = \App\Models\Tag::where('is_active', true)
            ->withCount('lists')
            ->orderBy('lists_count', 'desc')
            ->limit(10)
            ->get();
        
        return Inertia::render('Home', [
            'feedItems' => $latestLists, // Unified feed containing both lists and places
            'categories' => $categories,
            'trendingTags' => $trendingTags,
        ]);
    })->name('home');
    
    // Main dashboard (legacy route - redirect to home)
    Route::redirect('/dashboard', '/home')->name('dashboard');

    // Radiant template demo
    Route::get('/radiant-demo', function () {
        return Inertia::render('RadiantDemoSimple');
    })->name('radiant.demo');

    // Test Cloudflare Images upload
    Route::get('/test-cloudflare-upload', function () {
        return Inertia::render('TestCloudflareUpload');
    })->name('test.cloudflare.upload');

    // Profile routes
    Route::get('/profile', [\App\Http\Controllers\UserProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\UserProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User lists routes
    Route::get('/my-lists', function () {
        return Inertia::render('Lists/Index');
    })->name('lists.my');
    
    Route::get('/lists/create', function () {
        return Inertia::render('Lists/Create');
    })->name('lists.create');

    // Directory entry management
    Route::get('/places/create', function () {
        $categories = \App\Models\Category::whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Places/Create', [
            'categories' => $categories
        ]);
    })->name('places.create');

    Route::get('/places/{entry}/edit', function (\App\Models\DirectoryEntry $entry) {
        // Check if user can edit this entry
        if (!$entry->canBeEdited()) {
            abort(403, 'You do not have permission to edit this entry');
        }

        $categories = \App\Models\Category::whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('name')
            ->get();

        $entry->load(['category', 'location']);

        return Inertia::render('Places/Edit', [
            'entry' => $entry,
            'categories' => $categories
        ]);
    })->name('places.edit');


    // User specific routes
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/lists', function () {
            return Inertia::render('User/Lists/Index');
        })->name('lists');
        
        Route::get('/favorites', function () {
            return Inertia::render('User/Favorites/Index');
        })->name('favorites');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Pages)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin,manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');
    
    Route::get('/analytics', function () {
        return Inertia::render('Admin/Analytics/Index');
    })->name('analytics');
    
    Route::get('/media', [App\Http\Controllers\Admin\MediaController::class, 'index'])->name('media');
    
    Route::get('/lists', function () {
        return Inertia::render('Admin/Lists/Index');
    })->name('lists');
    
    Route::get('/users', function () {
        $filters = request()->only(['search', 'role', 'status']);
        
        $query = \App\Models\User::query();
        
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }
        
        $users = $query->latest()->paginate(20)->appends($filters);
        
        // Get stats
        $stats = [
            'total_users' => \App\Models\User::count(),
            'active_users' => \App\Models\User::whereNotNull('email_verified_at')->count(),
            'business_owners' => \App\Models\User::where('role', 'business_owner')->count(),
            'admins' => \App\Models\User::whereIn('role', ['admin', 'manager'])->count(),
        ];
        
        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    })->name('users');
    
    Route::get('/users/{user}', function ($user) {
        return Inertia::render('Admin/Users/Show', ['userId' => $user]);
    })->name('users.show');
    
    Route::get('/entries', function () {
        $filters = request()->only(['search', 'type', 'status', 'category_id']);
        
        $query = \App\Models\DirectoryEntry::with(['category.parent', 'location']);
        
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        $entries = $query->latest()->paginate(20)->appends($filters);
        
        // Get stats
        $stats = [
            'total_entries' => \App\Models\DirectoryEntry::count(),
            'published' => \App\Models\DirectoryEntry::where('status', 'published')->count(),
            'pending_review' => \App\Models\DirectoryEntry::where('status', 'pending_review')->count(),
            'featured' => \App\Models\DirectoryEntry::where('is_featured', true)->count(),
        ];
        
        // Get categories for filter
        $categories = \App\Models\Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Admin/Entries/Index', [
            'entries' => $entries,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    })->name('entries');
    
    Route::get('/categories', function () {
        return Inertia::render('Admin/Categories/Index');
    })->name('categories');
    
    Route::get('/list-categories', function () {
        return Inertia::render('Admin/ListCategories/Index');
    })->name('list-categories');
    
    Route::get('/tags', function () {
        return Inertia::render('Admin/Tags/Index');
    })->name('tags');
    
    Route::get('/reports', function () {
        return Inertia::render('Admin/Reports/Index');
    })->name('reports');
    
    Route::get('/settings', function () {
        return Inertia::render('Admin/Settings/Index');
    })->name('settings');
    
    Route::get('/bulk-import', function () {
        return Inertia::render('Admin/BulkImport');
    })->name('bulk-import');
    
    // Bulk import routes - accessible via web middleware
    Route::get('/bulk-import/template', [App\Http\Controllers\Api\Admin\BulkImportController::class, 'downloadTemplate'])->name('bulk-import.template');
    Route::post('/bulk-import/csv', [App\Http\Controllers\Api\Admin\BulkImportController::class, 'uploadCsv'])->name('bulk-import.upload');
});

/*
|--------------------------------------------------------------------------
| Admin API Routes (Data endpoints)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,manager'])->prefix('admin-data')->group(function () {
    // Dashboard stats
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // User management
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::post('/users', [UserManagementController::class, 'store']);
    Route::get('/users/stats', [UserManagementController::class, 'stats']);
    Route::get('/users/{user}', [UserManagementController::class, 'show']);
    Route::put('/users/{user}', [UserManagementController::class, 'update']);
    Route::put('/users/{user}/password', [UserManagementController::class, 'updatePassword']);
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
    Route::post('/users/bulk-update', [UserManagementController::class, 'bulkUpdate']);
    
    // Directory entries
    Route::get('/entries', [DirectoryEntryController::class, 'index']);
    Route::get('/entries/stats', [DirectoryEntryController::class, 'stats']);
    Route::post('/entries', [DirectoryEntryController::class, 'store']);
    Route::get('/entries/{entry}', [DirectoryEntryController::class, 'show']);
    Route::put('/entries/{entry}', [DirectoryEntryController::class, 'update']);
    Route::delete('/entries/{entry}', [DirectoryEntryController::class, 'destroy']);
    Route::post('/entries/bulk-update', [DirectoryEntryController::class, 'bulkUpdate']);
    
    // Media management
    Route::get('/media/{imageId}', [MediaController::class, 'show']);
    Route::delete('/media/{imageId}', [MediaController::class, 'destroy']);
    Route::post('/media/bulk-delete', [MediaController::class, 'bulkDelete']);
    Route::post('/media/cleanup', [MediaController::class, 'cleanup']);
    
    // Categories management
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);
    
    // List management
    Route::get('/lists', [ListManagementController::class, 'index']);
    Route::get('/lists/stats', [ListManagementController::class, 'stats']);
    Route::get('/lists/{list}', [ListManagementController::class, 'show']);
    Route::put('/lists/{list}', [ListManagementController::class, 'update']);
    Route::delete('/lists/{list}', [ListManagementController::class, 'destroy']);
    Route::post('/lists/bulk-update', [ListManagementController::class, 'bulkUpdate']);
    
    // List categories management
    Route::get('/list-categories', [ListCategoryController::class, 'index']);
    Route::get('/list-categories/stats', [ListCategoryController::class, 'stats']);
    Route::get('/list-categories/options', [ListCategoryController::class, 'options']);
    Route::post('/list-categories', [ListCategoryController::class, 'store']);
    Route::get('/list-categories/{category}', [ListCategoryController::class, 'show']);
    Route::put('/list-categories/{category}', [ListCategoryController::class, 'update']);
    Route::delete('/list-categories/{category}', [ListCategoryController::class, 'destroy']);
    Route::post('/list-categories/bulk-update', [ListCategoryController::class, 'bulkUpdate']);
    
    // Tags management
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/tags/stats', [TagController::class, 'stats']);
    Route::get('/tags/search', [TagController::class, 'search']);
    Route::get('/tags/popular', [TagController::class, 'popular']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::get('/tags/{tag}', [TagController::class, 'show']);
    Route::put('/tags/{tag}', [TagController::class, 'update']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
    Route::post('/tags/bulk-update', [TagController::class, 'bulkUpdate']);
});

/*
|--------------------------------------------------------------------------
| Business Owner Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:business_owner'])->prefix('business')->name('business.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Business/Index');
    })->name('index');
    
    Route::get('/claims', function () {
        return Inertia::render('Business/Claims/Index');
    })->name('claims');
});

/*
|--------------------------------------------------------------------------
| User Data API Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('data')->group(function () {
    // Personal Lists
    Route::get('/my-lists', [UserListController::class, 'myLists']);
    
    // Public Lists
    Route::get('/public-lists', [UserListController::class, 'publicLists']);
    Route::get('/list-categories', [UserListController::class, 'categories']);
    
    // List Management
    Route::get('/lists', [UserListController::class, 'index']);
    Route::post('/lists', [UserListController::class, 'store']);
    Route::get('/lists/{list}', [UserListController::class, 'show']);
    Route::put('/lists/{list}', [UserListController::class, 'update']);
    Route::delete('/lists/{list}', [UserListController::class, 'destroy']);
    
    // List items
    Route::put('/lists/{listId}/items/reorder', [UserListController::class, 'reorderItems']);
    Route::post('/lists/{list}/items', [UserListController::class, 'addItem']);
    Route::put('/lists/{list}/items/{item}', [UserListController::class, 'updateItem']);
    Route::delete('/lists/{list}/items/{item}', [UserListController::class, 'removeItem']);
    
    // Search entries
    Route::get('/directory-entries/search', [UserListController::class, 'searchEntries']);
    
    // List categories (public for all authenticated users)
    Route::get('/list-categories/all', [UserListController::class, 'getAllCategories']);
    
    // Tags endpoints (public for all authenticated users)
    Route::get('/tags/search', [UserListController::class, 'searchTags']);
    Route::post('/tags/validate', [UserListController::class, 'validateTag']);
    
    // List sharing
    Route::prefix('lists/{list}/shares')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ListShareController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\ListShareController::class, 'store']);
        Route::put('/{share}', [\App\Http\Controllers\Api\ListShareController::class, 'update']);
        Route::delete('/{share}', [\App\Http\Controllers\Api\ListShareController::class, 'destroy']);
    });
    
    // User search for sharing
    Route::get('/users/search', [\App\Http\Controllers\Api\ListShareController::class, 'searchUsers']);
    
    // Popular creators and stats
    Route::get('/popular-list-creators', [UserListController::class, 'popularCreators']);
    Route::get('/public-lists-stats', [UserListController::class, 'publicListsStats']);
    
    // Favorites
    Route::post('/favorites/lists/{list}', [UserListController::class, 'addToFavorites']);
    Route::delete('/favorites/lists/{list}', [UserListController::class, 'removeFromFavorites']);
    
    // Home feed
    Route::get('/home/feed', [\App\Http\Controllers\Api\HomeController::class, 'getFeed']);
    Route::get('/home/lists', [\App\Http\Controllers\Api\HomeController::class, 'loadMoreLists']);

});

// Public API routes (no auth required)
Route::prefix('data')->group(function () {
    Route::get('/users/{user}/public-lists', [UserListController::class, 'userPublicLists']);
});

/*
|--------------------------------------------------------------------------
| User Profile API Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('api/users')->group(function () {
    Route::post('/{username}/follow', [\App\Http\Controllers\UserProfileController::class, 'follow']);
    Route::post('/{username}/unfollow', [\App\Http\Controllers\UserProfileController::class, 'unfollow']);
    Route::post('/lists/{listId}/pin', [\App\Http\Controllers\UserProfileController::class, 'pinList']);
    Route::post('/lists/{listId}/unpin', [\App\Http\Controllers\UserProfileController::class, 'unpinList']);
});

/*
|--------------------------------------------------------------------------
| Catch-all routes for user profiles and lists (must be at the end)
|--------------------------------------------------------------------------
*/

// User profile followers/following pages
Route::get('/{username}/followers', [\App\Http\Controllers\UserProfileController::class, 'followers'])->name('user.followers');
Route::get('/{username}/following', [\App\Http\Controllers\UserProfileController::class, 'following'])->name('user.following');

// User public lists page
Route::get('/{userSlug}/lists', function ($userSlug) {
    $user = \App\Models\User::findByUrlSlug($userSlug);
    if (!$user) {
        abort(404);
    }
    
    // Use different view based on authentication
    if (auth()->check()) {
        return Inertia::render('User/Lists/Public', ['userId' => $user->id, 'username' => $user->name]);
    } else {
        return Inertia::render('User/PublicLists', ['user' => $user]);
    }
})->name('user.public.lists');

// Edit list (auth required)
Route::get('/{userSlug}/{slug}/edit', function ($userSlug, $slug) {
    $user = \App\Models\User::findByUrlSlug($userSlug);
    if (!$user) {
        abort(404);
    }
    $list = $user->lists()->where('slug', $slug)->firstOrFail();
    return Inertia::render('Lists/Edit', ['listId' => $list->id]);
})->middleware('auth')->name('lists.edit');

// Show list (public or authenticated)
Route::get('/{userSlug}/{slug}', function ($userSlug, $slug) {
    $user = \App\Models\User::findByUrlSlug($userSlug);
    if (!$user) {
        abort(404);
    }
    $list = $user->lists()->where('slug', $slug)->firstOrFail();
    
    // Check visibility permissions
    $currentUser = auth()->user();
    
    // Check if list can be viewed
    if (!$list->canView($currentUser)) {
        abort(404);
    }
    
    // For public lists, redirect unauthenticated users to login with proper message
    if (!$currentUser && $list->isPublic()) {
        return redirect()->route('login')->with([
            'message' => 'Sign up to view full lists and save your favorites.',
            'redirect_to' => request()->fullUrl()
        ]);
    }
    
    // Increment view count for published lists (not drafts)
    if ($list->isPublished() && !$list->isOwnedBy($currentUser)) {
        $list->incrementViewCount();
    }
    
    // Load list with all necessary relationships
    $list->load(['user', 'category', 'tags', 'items.directoryEntry.location', 'items.directoryEntry.category']);
    
    // Decide which view to use based on authentication
    if ($currentUser) {
        // Authenticated view
        return Inertia::render('Lists/Show', ['listId' => $list->id]);
    } else {
        // Public view (only for unlisted lists that are viewable by guests)
        return Inertia::render('Lists/PublicShow', [
            'list' => $list,
            'canEdit' => false,
        ]);
    }
})->name('lists.show');


// New category-based entry URLs: /{parent-category}/{child-category}/{entry-slug}
Route::get('/{parentCategorySlug}/{childCategorySlug}/{entrySlug}', function ($parentCategorySlug, $childCategorySlug, $entrySlug) {
    // Find the parent category
    $parentCategory = \App\Models\Category::where('slug', $parentCategorySlug)
        ->whereNull('parent_id')
        ->first();
    
    if (!$parentCategory) {
        abort(404);
    }
    
    // Find the child category
    $childCategory = \App\Models\Category::where('slug', $childCategorySlug)
        ->where('parent_id', $parentCategory->id)
        ->first();
    
    if (!$childCategory) {
        abort(404);
    }
    
    // Find the entry
    $entry = \App\Models\DirectoryEntry::where('slug', $entrySlug)
        ->where('category_id', $childCategory->id)
        ->where('status', 'published')
        ->first();
    
    if (!$entry) {
        abort(404);
    }
    
    // Load necessary relationships
    $entry->load(['category', 'location', 'owner', 'createdBy']);
    
    // Get related entries from the same category
    $relatedEntries = \App\Models\DirectoryEntry::where('status', 'published')
        ->where('category_id', $entry->category_id)
        ->where('id', '!=', $entry->id)
        ->with(['category', 'location'])
        ->limit(4)
        ->get();
    
    return Inertia::render('Places/Show', [
        'entry' => $entry,
        'relatedEntries' => $relatedEntries,
        'parentCategory' => $parentCategory,
        'childCategory' => $childCategory,
    ]);
})->name('places.entry');

// Deployment route (SECURE THIS!)
Route::get('/deploy/{token}', function ($token) {
    // IMPORTANT: Set this in your .env file
    $validToken = env('DEPLOY_TOKEN', 'change-this-secret-token');
    
    if ($token !== $validToken) {
        abort(403, 'Invalid deployment token');
    }
    
    // Only allow in production
    if (app()->environment('local')) {
        return response('Deployment not available in local environment', 400);
    }
    
    // Run deployment commands
    $output = [];
    $commands = [
        'cd ' . base_path() . ' && git pull origin main 2>&1',
        'composer install --optimize-autoloader --no-dev 2>&1',
        'npm install 2>&1',
        'npm run build 2>&1',
        'php artisan migrate --force 2>&1',
        'php artisan config:cache 2>&1',
        'php artisan route:cache 2>&1',
        'php artisan view:cache 2>&1',
    ];
    
    foreach ($commands as $command) {
        $output[] = '$ ' . $command;
        $result = shell_exec($command);
        $output[] = $result ?: 'Command executed successfully';
        $output[] = '';
    }
    
    return response('<pre>' . implode("\n", $output) . '</pre>')
        ->header('Content-Type', 'text/html');
})->name('deploy');

require __DIR__.'/auth.php';

// User profile main page (must be last to avoid conflicts)
Route::get('/{username}', [\App\Http\Controllers\UserProfileController::class, 'show'])->name('user.profile');