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
            'module.feature:ImageOverlay'
        ])
        ->group(function () {
            Route::get('image-overlay/stats', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Admin\ImageOverlayController::class, 'stats'])
                ->name('admin.image-overlay.stats')
                ->middleware('permission:view_image_overlay_stats');
            
            Route::get('image-overlay/users-with-uploads', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Admin\ImageOverlayController::class, 'usersWithUploads'])
                ->name('admin.image-overlay.users-with-uploads')
                ->middleware('permission:view_image_overlay_stats');
            
            Route::get('image-overlay/image-overlays', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Admin\ImageOverlayController::class, 'index'])
                ->name('admin.image-overlay.image-overlays')
                ->middleware('permission:view_all_image_overlay');
            
            Route::delete('image-overlay/image-overlays/{id}', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Admin\ImageOverlayController::class, 'destroy'])
                ->name('admin.image-overlay.image-overlays.delete')
                ->middleware('permission:delete_any_image_overlay');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:ImageOverlay',
        ])
        ->group(function () {
            // Browse overlays (read-only; use_image_overlay)
            Route::get('image-overlay/browse', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'browseIndex'])
                ->name('customer.image-overlay.browse')
                ->middleware('permission:use_image_overlay');
            Route::get('image-overlay/browse/folders', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'browseFolders'])
                ->name('customer.image-overlay.browse.folders')
                ->middleware('permission:use_image_overlay');
            Route::get('image-overlay/browse/{id}', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'browseShow'])
                ->name('customer.image-overlay.browse.show')
                ->middleware('permission:use_image_overlay');

            // Image overlay operations
            Route::post('image-overlay/upload', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'upload'])
                ->name('customer.image-overlay.upload')
                ->middleware('permission:upload_image_overlay');
            
            Route::post('image-overlay/bulk-upload', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'bulkUpload'])
                ->name('customer.image-overlay.bulk-upload')
                ->middleware('permission:bulk_upload_image_overlay');
            
            Route::get('image-overlay/image-overlays', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'index'])
                ->name('customer.image-overlay.image-overlays')
                ->middleware('permission:view_image_overlay');
            
            Route::get('image-overlay/image-overlays/{id}', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'show'])
                ->name('customer.image-overlay.image-overlays.show')
                ->middleware('permission:view_image_overlay');
            
            Route::put('image-overlay/image-overlays/{id}', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'update'])
                ->name('customer.image-overlay.image-overlays.update')
                ->middleware('permission:manage_image_overlay');
            
            Route::delete('image-overlay/image-overlays/{id}', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'destroy'])
                ->name('customer.image-overlay.image-overlays.delete')
                ->middleware('permission:manage_image_overlay');
            
            // Folders
            Route::get('image-overlay/folders', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'listFolders'])
                ->name('customer.image-overlay.folders')
                ->middleware('permission:manage_image_overlay_folders');
            
            Route::post('image-overlay/folders', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'createFolder'])
                ->name('customer.image-overlay.folders.create')
                ->middleware('permission:manage_image_overlay_folders');
            
            Route::put('image-overlay/folders/{id}', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'updateFolder'])
                ->name('customer.image-overlay.folders.update')
                ->middleware('permission:manage_image_overlay_folders');
            
            Route::delete('image-overlay/folders/{id}', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'deleteFolder'])
                ->name('customer.image-overlay.folders.delete')
                ->middleware('permission:manage_image_overlay_folders');
            
            // Upload queue status
            Route::get('image-overlay/upload-queue/{id}', [\Modules\ImageOverlay\Http\Controllers\Api\V1\Customer\ImageOverlayController::class, 'getUploadQueueStatus'])
                ->name('customer.image-overlay.upload-queue')
                ->middleware('permission:upload_image_overlay');
        });
});
