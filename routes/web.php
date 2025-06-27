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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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
        return Inertia::render('Admin/Users/Index');
    })->name('users');
    
    Route::get('/users/{user}', function ($user) {
        return Inertia::render('Admin/Users/Show', ['userId' => $user]);
    })->name('users.show');
    
    Route::get('/entries', function () {
        return Inertia::render('Admin/Entries/Index');
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
    return Inertia::render('User/Lists/Public', ['userId' => $user->id, 'username' => $user->name]);
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

// Show list (public)
Route::get('/{userSlug}/{slug}', function ($userSlug, $slug) {
    $user = \App\Models\User::findByUrlSlug($userSlug);
    if (!$user) {
        abort(404);
    }
    $list = $user->lists()->where('slug', $slug)->firstOrFail();
    return Inertia::render('Lists/Show', ['listId' => $list->id]);
})->name('lists.show');

require __DIR__.'/auth.php';