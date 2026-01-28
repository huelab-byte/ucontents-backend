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
            'module.feature:BgmLibrary'
        ])
        ->group(function () {
            Route::get('bgm-library/stats', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Admin\BgmLibraryController::class, 'stats'])
                ->name('admin.bgm-library.stats')
                ->middleware('permission:view_bgm_stats');
            
            Route::get('bgm-library/users-with-uploads', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Admin\BgmLibraryController::class, 'usersWithUploads'])
                ->name('admin.bgm-library.users-with-uploads')
                ->middleware('permission:view_bgm_stats');
            
            Route::get('bgm-library/bgm', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Admin\BgmLibraryController::class, 'index'])
                ->name('admin.bgm-library.bgm')
                ->middleware('permission:view_all_bgm');
            
            Route::delete('bgm-library/bgm/{id}', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Admin\BgmLibraryController::class, 'destroy'])
                ->name('admin.bgm-library.bgm.delete')
                ->middleware('permission:delete_any_bgm');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:BgmLibrary',
        ])
        ->group(function () {
            // BGM operations
            Route::post('bgm-library/upload', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'upload'])
                ->name('customer.bgm-library.upload')
                ->middleware('permission:upload_bgm');
            
            Route::post('bgm-library/bulk-upload', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'bulkUpload'])
                ->name('customer.bgm-library.bulk-upload')
                ->middleware('permission:bulk_upload_bgm');
            
            Route::get('bgm-library/bgm', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'index'])
                ->name('customer.bgm-library.bgm')
                ->middleware('permission:view_bgm');
            
            Route::get('bgm-library/bgm/{id}', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'show'])
                ->name('customer.bgm-library.bgm.show')
                ->middleware('permission:view_bgm');
            
            Route::put('bgm-library/bgm/{id}', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'update'])
                ->name('customer.bgm-library.bgm.update')
                ->middleware('permission:manage_bgm');
            
            Route::delete('bgm-library/bgm/{id}', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'destroy'])
                ->name('customer.bgm-library.bgm.delete')
                ->middleware('permission:manage_bgm');
            
            // Folders
            Route::get('bgm-library/folders', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'listFolders'])
                ->name('customer.bgm-library.folders')
                ->middleware('permission:manage_bgm_folders');
            
            Route::post('bgm-library/folders', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'createFolder'])
                ->name('customer.bgm-library.folders.create')
                ->middleware('permission:manage_bgm_folders');
            
            Route::put('bgm-library/folders/{id}', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'updateFolder'])
                ->name('customer.bgm-library.folders.update')
                ->middleware('permission:manage_bgm_folders');
            
            Route::delete('bgm-library/folders/{id}', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'deleteFolder'])
                ->name('customer.bgm-library.folders.delete')
                ->middleware('permission:manage_bgm_folders');
            
            // Upload queue status
            Route::get('bgm-library/upload-queue/{id}', [\Modules\BgmLibrary\Http\Controllers\Api\V1\Customer\BgmLibraryController::class, 'getUploadQueueStatus'])
                ->name('customer.bgm-library.upload-queue')
                ->middleware('permission:upload_bgm');
        });
});
