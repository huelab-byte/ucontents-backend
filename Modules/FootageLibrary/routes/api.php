<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // Admin routes
    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:FootageLibrary'
        ])
        ->group(function () {
            Route::get('footage-library/stats', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Admin\FootageLibraryController::class, 'stats'])
                ->name('admin.footage-library.stats')
                ->middleware('permission:view_footage_stats');
            
            Route::get('footage-library/users-with-uploads', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Admin\FootageLibraryController::class, 'usersWithUploads'])
                ->name('admin.footage-library.users-with-uploads')
                ->middleware('permission:view_footage_stats');
            
            Route::get('footage-library/footage', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Admin\FootageLibraryController::class, 'index'])
                ->name('admin.footage-library.footage')
                ->middleware('permission:view_all_footage');
            
            Route::delete('footage-library/footage/{id}', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Admin\FootageLibraryController::class, 'destroy'])
                ->name('admin.footage-library.footage.delete')
                ->middleware('permission:delete_any_footage');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:FootageLibrary',
        ])
        ->group(function () {
            // Footage operations
            Route::post('footage-library/upload', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'upload'])
                ->name('customer.footage-library.upload')
                ->middleware('permission:upload_footage');
            
            Route::post('footage-library/bulk-upload', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'bulkUpload'])
                ->name('customer.footage-library.bulk-upload')
                ->middleware('permission:bulk_upload_footage');
            
            Route::get('footage-library/footage', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'index'])
                ->name('customer.footage-library.footage')
                ->middleware('permission:view_footage');
            
            Route::get('footage-library/footage/{id}', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'show'])
                ->name('customer.footage-library.footage.show')
                ->middleware('permission:view_footage');
            
            Route::put('footage-library/footage/{id}', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'update'])
                ->name('customer.footage-library.footage.update')
                ->middleware('permission:manage_footage');
            
            Route::delete('footage-library/footage/{id}', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'destroy'])
                ->name('customer.footage-library.footage.delete')
                ->middleware('permission:manage_footage');
            
            Route::post('footage-library/footage/{id}/generate-metadata', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'generateMetadata'])
                ->name('customer.footage-library.footage.generate-metadata')
                ->middleware('permission:manage_footage');
            
            // Search
            Route::post('footage-library/search', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'search'])
                ->name('customer.footage-library.search')
                ->middleware('permission:search_footage');
            
            // Folders
            Route::get('footage-library/folders', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'listFolders'])
                ->name('customer.footage-library.folders')
                ->middleware('permission:manage_footage_folders');
            
            Route::post('footage-library/folders', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'createFolder'])
                ->name('customer.footage-library.folders.create')
                ->middleware('permission:manage_footage_folders');
            
            Route::put('footage-library/folders/{id}', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'updateFolder'])
                ->name('customer.footage-library.folders.update')
                ->middleware('permission:manage_footage_folders');
            
            Route::delete('footage-library/folders/{id}', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'deleteFolder'])
                ->name('customer.footage-library.folders.delete')
                ->middleware('permission:manage_footage_folders');
            
            // Upload queue status
            Route::get('footage-library/upload-queue/{id}', [\Modules\FootageLibrary\Http\Controllers\Api\V1\Customer\FootageLibraryController::class, 'getUploadQueueStatus'])
                ->name('customer.footage-library.upload-queue')
                ->middleware('permission:upload_footage');
        });
});
