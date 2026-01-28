<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\StorageManagement\Http\Controllers\Api\V1\Admin\StorageManagementController;
use Modules\StorageManagement\Http\Controllers\Api\V1\Customer\FileUploadController;

Route::prefix('v1')->group(function () {
    
    // Admin routes
    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:StorageManagement'
        ])
        ->group(function () {
            // View storage configuration
            Route::get('storage/config', [StorageManagementController::class, 'index'])
                ->name('admin.storage.config')
                ->middleware('permission:view_storage_config|manage_storage_config');
            Route::get('storage/configs', [StorageManagementController::class, 'list'])
                ->name('admin.storage.configs')
                ->middleware('permission:view_storage_config|manage_storage_config');
            
            // Create/Update storage configuration
            Route::post('storage/config', [StorageManagementController::class, 'store'])
                ->name('admin.storage.config.store')
                ->middleware('permission:update_storage_config|manage_storage_config');
            Route::put('storage/config/{id}', [StorageManagementController::class, 'update'])
                ->name('admin.storage.config.update')
                ->middleware('permission:update_storage_config|manage_storage_config');
            Route::post('storage/config/{id}/activate', [StorageManagementController::class, 'activate'])
                ->name('admin.storage.config.activate')
                ->middleware('permission:update_storage_config|manage_storage_config');
            Route::delete('storage/config/{id}', [StorageManagementController::class, 'destroy'])
                ->name('admin.storage.config.delete')
                ->middleware('permission:update_storage_config|manage_storage_config');
            
            // Test connection
            Route::post('storage/config/test', [StorageManagementController::class, 'testConnection'])
                ->name('admin.storage.config.test')
                ->middleware('permission:update_storage_config|manage_storage_config');
            
            // Storage analytics/usage
            Route::get('storage/usage', [StorageManagementController::class, 'usage'])
                ->name('admin.storage.usage')
                ->middleware('permission:view_storage_analytics|manage_storage_config');
            
            // Storage migration
            Route::post('storage/migrate', [StorageManagementController::class, 'migrate'])
                ->name('admin.storage.migrate')
                ->middleware('permission:migrate_storage|manage_storage_config');
            
            // Storage cleanup
            Route::post('storage/cleanup', [StorageManagementController::class, 'cleanup'])
                ->name('admin.storage.cleanup')
                ->middleware('permission:cleanup_storage|manage_storage_config');
            
            // Admin file upload (uses same controller as customer)
            Route::post('storage/upload', [FileUploadController::class, 'upload'])
                ->name('admin.storage.upload')
                ->middleware('permission:upload_files|manage_storage_config');
            
            // Admin file download
            Route::get('storage/files/{id}/download', [FileUploadController::class, 'download'])
                ->name('admin.storage.download')
                ->middleware('permission:upload_files|manage_storage_config');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:StorageManagement',
        ])
        ->group(function () {
            Route::post('storage/upload', [FileUploadController::class, 'upload'])
                ->name('customer.storage.upload')
                ->middleware('permission:upload_files');
            Route::post('storage/bulk-upload', [FileUploadController::class, 'bulkUpload'])
                ->name('customer.storage.bulk-upload')
                ->middleware('permission:bulk_upload_files|upload_files');
            Route::get('storage/files/{id}/download', [FileUploadController::class, 'download'])
                ->name('customer.storage.download')
                ->middleware('permission:upload_files');
        });
});
