<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\DirectoryEntryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
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

// Public places browsing routes
Route::get('/places', function () {
    $entries = \App\Models\DirectoryEntry::where('status', 'published')
        ->with(['category.parent', 'location'])
        ->latest()
        ->paginate(12);
    
    $categories = \App\Models\Category::whereNull('parent_id')
        ->withCount('directoryEntries')
        ->orderBy('order_index')
        ->get();
    
    return Inertia::render('Places/Index', [
        'entries' => $entries,
        'categories' => $categories,
    ]);
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



/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    // Main dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
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
    
    // Categories management
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);
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

    
    // Popular creators and stats
    Route::get('/popular-list-creators', [UserListController::class, 'popularCreators']);
    Route::get('/public-lists-stats', [UserListController::class, 'publicListsStats']);
    
    // Favorites
    Route::post('/favorites/lists/{list}', [UserListController::class, 'addToFavorites']);
    Route::delete('/favorites/lists/{list}', [UserListController::class, 'removeFromFavorites']);

});

// Public API routes (no auth required)
Route::prefix('data')->group(function () {
    Route::get('/users/{user}/public-lists', [UserListController::class, 'userPublicLists']);
});

/*
|--------------------------------------------------------------------------
| Catch-all routes for user profiles and lists (must be at the end)
|--------------------------------------------------------------------------
*/

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
    
    if ($list->isPrivate()) {
        // Private lists can only be viewed by owner or admins
        if (!$currentUser || (!$list->isOwnedBy($currentUser) && !in_array($currentUser->role, ['admin', 'manager']))) {
            abort(404);
        }
    }
    
    // Increment view count for public/unlisted lists
    if ($list->isViewableByGuests()) {
        $list->incrementViewCount();
    }
    
    // Load list with all necessary relationships
    $list->load(['user', 'items.directoryEntry.location', 'items.directoryEntry.category']);
    
    // Decide which view to use based on authentication
    if ($currentUser) {
        // Authenticated view
        return Inertia::render('Lists/Show', ['listId' => $list->id]);
    } else {
        // Public view
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

require __DIR__.'/auth.php';