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
            'module.feature:ImageLibrary'
        ])
        ->group(function () {
            Route::get('image-library/stats', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Admin\ImageLibraryController::class, 'stats'])
                ->name('admin.image-library.stats')
                ->middleware('permission:view_image_stats');
            
            Route::get('image-library/users-with-uploads', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Admin\ImageLibraryController::class, 'usersWithUploads'])
                ->name('admin.image-library.users-with-uploads')
                ->middleware('permission:view_image_stats');
            
            Route::get('image-library/images', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Admin\ImageLibraryController::class, 'index'])
                ->name('admin.image-library.images')
                ->middleware('permission:view_all_image');
            
            Route::delete('image-library/images/{id}', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Admin\ImageLibraryController::class, 'destroy'])
                ->name('admin.image-library.images.delete')
                ->middleware('permission:delete_any_image');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:ImageLibrary',
        ])
        ->group(function () {
            // Browse shared library (read-only; use_image_library)
            Route::get('image-library/browse/folders', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'browseFolders'])
                ->name('customer.image-library.browse.folders')
                ->middleware('permission:use_image_library');
            Route::get('image-library/browse', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'browseIndex'])
                ->name('customer.image-library.browse')
                ->middleware('permission:use_image_library');
            Route::get('image-library/browse/{id}', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'browseShow'])
                ->name('customer.image-library.browse.show')
                ->middleware('permission:use_image_library');

            // Image operations
            Route::post('image-library/upload', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'upload'])
                ->name('customer.image-library.upload')
                ->middleware('permission:upload_image');
            
            Route::post('image-library/bulk-upload', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'bulkUpload'])
                ->name('customer.image-library.bulk-upload')
                ->middleware('permission:bulk_upload_image');
            
            Route::get('image-library/images', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'index'])
                ->name('customer.image-library.images')
                ->middleware('permission:view_image');
            
            Route::get('image-library/images/{id}', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'show'])
                ->name('customer.image-library.images.show')
                ->middleware('permission:view_image');
            
            Route::put('image-library/images/{id}', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'update'])
                ->name('customer.image-library.images.update')
                ->middleware('permission:manage_image');
            
            Route::delete('image-library/images/{id}', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'destroy'])
                ->name('customer.image-library.images.delete')
                ->middleware('permission:manage_image');
            
            // Folders
            Route::get('image-library/folders', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'listFolders'])
                ->name('customer.image-library.folders')
                ->middleware('permission:manage_image_folders');
            
            Route::post('image-library/folders', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'createFolder'])
                ->name('customer.image-library.folders.create')
                ->middleware('permission:manage_image_folders');
            
            Route::put('image-library/folders/{id}', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'updateFolder'])
                ->name('customer.image-library.folders.update')
                ->middleware('permission:manage_image_folders');
            
            Route::delete('image-library/folders/{id}', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'deleteFolder'])
                ->name('customer.image-library.folders.delete')
                ->middleware('permission:manage_image_folders');
            
            // Upload queue status
            Route::get('image-library/upload-queue/{id}', [\Modules\ImageLibrary\Http\Controllers\Api\V1\Customer\ImageLibraryController::class, 'getUploadQueueStatus'])
                ->name('customer.image-library.upload-queue')
                ->middleware('permission:upload_image');
        });
});
