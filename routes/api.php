<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserListController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\DirectoryEntryController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\DashboardController;

// Public routes
Route::get('/locations/search', [LocationController::class, 'search']);
Route::get('/locations/nearby', [LocationController::class, 'nearby']);
Route::get('/locations/{location:slug}', [LocationController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/entries', [DirectoryEntryController::class, 'index']);
Route::get('/entries/nearby', [DirectoryEntryController::class, 'nearbyEntries']);
Route::get('/entries/{entry:slug}', [DirectoryEntryController::class, 'show']);

// Authenticated routes (using web middleware for session auth)
Route::middleware(['web', 'auth'])->group(function () {
    // Return authenticated user info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Create a new directory entry (any logged‑in user)
    Route::post('/entries', [DirectoryEntryController::class, 'store']);

    // Image upload routes
    Route::post('/entries/upload-image', [DirectoryEntryController::class, 'uploadImage']);
    Route::delete('/entries/delete-image', [DirectoryEntryController::class, 'deleteImage']);

    // Update an entry (admin, manager, editor, or business_owner)
    Route::middleware('role:admin,manager,editor,business_owner')->group(function () {
        Route::put('/entries/{directoryEntry:id}', [DirectoryEntryController::class, 'update']);
    });

    // Delete or publish an entry, or view entries pending review (admin or manager)
    Route::middleware('role:admin,manager')->group(function () {
        Route::delete('/entries/{entry}', [DirectoryEntryController::class, 'destroy']);
        Route::post('/entries/{entry}/publish', [DirectoryEntryController::class, 'publish']);
        Route::get('/entries/pending-review', [DirectoryEntryController::class, 'pendingReview']);
    });
});

// Keep sanctum routes for API access
Route::middleware('auth:sanctum')->group(function () {
    // Admin routes - Fixed to match what Vue components expect
    Route::prefix('admin')->middleware('role:admin,manager')->group(function () {
        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        
        // User management - THESE are the correct paths the Vue components use
        Route::get('/users', [UserManagementController::class, 'index']);
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
    });
});