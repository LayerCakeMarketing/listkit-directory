<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\RegionManagementController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\PlaceController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ListManagementController;
use App\Http\Controllers\Api\Admin\ListCategoryController;
use App\Http\Controllers\Api\Admin\TagController;
use App\Http\Controllers\Api\Admin\CuratedListController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Region Management Routes
Route::prefix('regions')->group(function () {
    Route::get('/', [RegionManagementController::class, 'index'])->name('regions.index');
    Route::get('/create', [RegionManagementController::class, 'create'])->name('regions.create');
    Route::post('/', [RegionManagementController::class, 'store'])->name('regions.store');
    Route::get('/{region}/edit', [RegionManagementController::class, 'edit'])->name('regions.edit');
    Route::put('/{region}', [RegionManagementController::class, 'update'])->name('regions.update');
    Route::put('/{region}/boundaries', [RegionManagementController::class, 'updateBoundaries'])->name('regions.update-boundaries');
    Route::post('/{region}/cover-image', [RegionManagementController::class, 'uploadCoverImage'])->name('regions.upload-cover');
    Route::delete('/{region}/cover-image', [RegionManagementController::class, 'removeCoverImage'])->name('regions.remove-cover');
    Route::put('/{region}/featured-places', [RegionManagementController::class, 'updateFeaturedPlaces'])->name('regions.featured-places');
    Route::post('/bulk-update', [RegionManagementController::class, 'bulkUpdate'])->name('regions.bulk-update');
    Route::delete('/{region}', [RegionManagementController::class, 'destroy'])->name('regions.destroy');
    Route::get('/{region}/preview', [RegionManagementController::class, 'preview'])->name('regions.preview');
});

// Dashboard
Route::get('/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
})->name('dashboard');

// Analytics
Route::get('/analytics', function () {
    return Inertia::render('Admin/Analytics/Index');
})->name('analytics');

// Media Management
Route::get('/media', [MediaController::class, 'index'])->name('media');

// Settings
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::put('/settings/{key}', [SettingsController::class, 'updateSingle'])->name('settings.update.single');

// List Management
Route::get('/lists', function () {
    return Inertia::render('Admin/Lists/Index');
})->name('lists');

// User Management
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

// Place Management
Route::get('/places', function () {
    $filters = request()->only(['search', 'type', 'status', 'category_id']);
    
    $query = \App\Models\Place::with(['category.parent', 'location']);
    
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
    
    $places = $query->latest()->paginate(20)->appends($filters);
    
    // Get stats
    $stats = [
        'total_places' => \App\Models\Place::count(),
        'published' => \App\Models\Place::where('status', 'published')->count(),
        'pending_review' => \App\Models\Place::where('status', 'pending_review')->count(),
        'featured' => \App\Models\Place::where('is_featured', true)->count(),
    ];
    
    // Get categories for filter
    $categories = \App\Models\Category::whereNull('parent_id')
        ->with('children')
        ->orderBy('name')
        ->get();
    
    return Inertia::render('Admin/Places/Index', [
        'places' => $places,
        'stats' => $stats,
        'categories' => $categories,
        'filters' => $filters,
    ]);
})->name('places');

// Categories
Route::get('/categories', function () {
    return Inertia::render('Admin/Categories/Index');
})->name('categories');

// List Categories
Route::get('/list-categories', function () {
    return Inertia::render('Admin/ListCategories/Index');
})->name('list-categories');

// Tags
Route::get('/tags', function () {
    return Inertia::render('Admin/Tags/Index');
})->name('tags');

// Reports
Route::get('/reports', function () {
    return Inertia::render('Admin/Reports/Index');
})->name('reports');

// Bulk Import
Route::get('/bulk-import', function () {
    return Inertia::render('Admin/BulkImport');
})->name('bulk-import');

// Bulk import routes - accessible via web middleware
Route::get('/bulk-import/template', [App\Http\Controllers\Api\Admin\BulkImportController::class, 'downloadTemplate'])->name('bulk-import.template');
Route::post('/bulk-import/csv', [App\Http\Controllers\Api\Admin\BulkImportController::class, 'uploadCsv'])->name('bulk-import.upload');

/*
|--------------------------------------------------------------------------
| Admin API Routes (Data endpoints)
|--------------------------------------------------------------------------
*/
Route::prefix('data')->group(function () {
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
    
    // Places
    Route::get('/places', [PlaceController::class, 'index']);
    Route::get('/places/stats', [PlaceController::class, 'stats']);
    Route::post('/places', [PlaceController::class, 'store']);
    Route::get('/places/{place}', [PlaceController::class, 'show']);
    Route::put('/places/{place}', [PlaceController::class, 'update']);
    Route::delete('/places/{place}', [PlaceController::class, 'destroy']);
    Route::post('/places/bulk-update', [PlaceController::class, 'bulkUpdate']);
    
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
    Route::post('/lists/{list}/status', [ListManagementController::class, 'updateStatus']);
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

// Note: Curated Lists Management routes have been moved to api.php under the admin middleware group