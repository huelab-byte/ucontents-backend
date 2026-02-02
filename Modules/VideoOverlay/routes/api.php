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
            'module.feature:VideoOverlay'
        ])
        ->group(function () {
            Route::get('video-overlay/stats', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Admin\VideoOverlayController::class, 'stats'])
                ->name('admin.video-overlay.stats')
                ->middleware('permission:view_video_overlay_stats');
            
            Route::get('video-overlay/users-with-uploads', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Admin\VideoOverlayController::class, 'usersWithUploads'])
                ->name('admin.video-overlay.users-with-uploads')
                ->middleware('permission:view_video_overlay_stats');
            
            Route::get('video-overlay/video-overlays', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Admin\VideoOverlayController::class, 'index'])
                ->name('admin.video-overlay.video-overlays')
                ->middleware('permission:view_all_video_overlay');
            
            Route::delete('video-overlay/video-overlays/{id}', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Admin\VideoOverlayController::class, 'destroy'])
                ->name('admin.video-overlay.video-overlays.delete')
                ->middleware('permission:delete_any_video_overlay');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:VideoOverlay',
        ])
        ->group(function () {
            // Browse shared overlays (read-only; use_video_overlay)
            Route::get('video-overlay/browse/folders', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'browseFolders'])
                ->name('customer.video-overlay.browse.folders')
                ->middleware('permission:use_video_overlay');
            Route::get('video-overlay/browse', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'browseIndex'])
                ->name('customer.video-overlay.browse')
                ->middleware('permission:use_video_overlay');
            Route::get('video-overlay/browse/{id}', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'browseShow'])
                ->name('customer.video-overlay.browse.show')
                ->middleware('permission:use_video_overlay');

            // Video overlay operations
            Route::post('video-overlay/upload', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'upload'])
                ->name('customer.video-overlay.upload')
                ->middleware('permission:upload_video_overlay');
            
            Route::get('video-overlay/video-overlays', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'index'])
                ->name('customer.video-overlay.video-overlays')
                ->middleware('permission:view_video_overlay');
            
            Route::get('video-overlay/video-overlays/{id}', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'show'])
                ->name('customer.video-overlay.video-overlays.show')
                ->middleware('permission:view_video_overlay');
            
            Route::put('video-overlay/video-overlays/{id}', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'update'])
                ->name('customer.video-overlay.video-overlays.update')
                ->middleware('permission:manage_video_overlay');
            
            Route::delete('video-overlay/video-overlays/{id}', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'destroy'])
                ->name('customer.video-overlay.video-overlays.delete')
                ->middleware('permission:manage_video_overlay');
            
            // Folders
            Route::get('video-overlay/folders', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'listFolders'])
                ->name('customer.video-overlay.folders')
                ->middleware('permission:manage_video_overlay_folders');
            
            Route::post('video-overlay/folders', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'createFolder'])
                ->name('customer.video-overlay.folders.create')
                ->middleware('permission:manage_video_overlay_folders');
            
            Route::put('video-overlay/folders/{id}', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'updateFolder'])
                ->name('customer.video-overlay.folders.update')
                ->middleware('permission:manage_video_overlay_folders');
            
            Route::delete('video-overlay/folders/{id}', [\Modules\VideoOverlay\Http\Controllers\Api\V1\Customer\VideoOverlayController::class, 'deleteFolder'])
                ->name('customer.video-overlay.folders.delete')
                ->middleware('permission:manage_video_overlay_folders');
        });
});
