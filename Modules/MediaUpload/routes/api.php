<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use \Modules\MediaUpload\Http\Controllers\Api\V1\Customer\MediaUploadFolderController;
use \Modules\MediaUpload\Http\Controllers\Api\V1\Customer\CaptionTemplateController;
use \Modules\MediaUpload\Http\Controllers\Api\V1\Customer\MediaUploadController;

Route::prefix('v1')->group(function () {
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:MediaUpload',
        ])
        ->group(function () {
            Route::get('media-upload/folders', [MediaUploadFolderController::class, 'listFolders'])
                ->name('customer.media-upload.folders')
                ->middleware('permission:manage_media_upload_folders');
            Route::post('media-upload/folders', [MediaUploadFolderController::class, 'createFolder'])
                ->name('customer.media-upload.folders.create')
                ->middleware('permission:manage_media_upload_folders');
            Route::get('media-upload/folders/{id}', [MediaUploadFolderController::class, 'show'])
                ->name('customer.media-upload.folders.show')
                ->middleware('permission:manage_media_upload_folders');
            Route::put('media-upload/folders/{id}', [MediaUploadFolderController::class, 'updateFolder'])
                ->name('customer.media-upload.folders.update')
                ->middleware('permission:manage_media_upload_folders');
            Route::delete('media-upload/folders/{id}', [MediaUploadFolderController::class, 'deleteFolder'])
                ->name('customer.media-upload.folders.delete')
                ->middleware('permission:manage_media_upload_folders');
            Route::get('media-upload/folders/{id}/content-settings', [MediaUploadFolderController::class, 'getContentSettings'])
                ->name('customer.media-upload.folders.content-settings')
                ->middleware('permission:manage_media_upload_folders');
            Route::put('media-upload/folders/{id}/content-settings', [MediaUploadFolderController::class, 'updateContentSettings'])
                ->name('customer.media-upload.folders.content-settings.update')
                ->middleware('permission:manage_media_upload_folders');

            Route::get('media-upload/caption-templates', [CaptionTemplateController::class, 'index'])
                ->name('customer.media-upload.caption-templates')
                ->middleware('permission:manage_caption_templates');
            Route::post('media-upload/caption-templates', [CaptionTemplateController::class, 'store'])
                ->name('customer.media-upload.caption-templates.create')
                ->middleware('permission:manage_caption_templates');
            Route::put('media-upload/caption-templates/{id}', [CaptionTemplateController::class, 'update'])
                ->name('customer.media-upload.caption-templates.update')
                ->middleware('permission:manage_caption_templates');
            Route::delete('media-upload/caption-templates/{id}', [CaptionTemplateController::class, 'destroy'])
                ->name('customer.media-upload.caption-templates.delete')
                ->middleware('permission:manage_caption_templates');

            Route::post('media-upload/bulk-upload', [MediaUploadController::class, 'bulkUpload'])
                ->name('customer.media-upload.bulk-upload')
                ->middleware('permission:upload_media');
            Route::get('media-upload/uploads', [MediaUploadController::class, 'index'])
                ->name('customer.media-upload.uploads')
                ->middleware('permission:manage_media_uploads');
            Route::get('media-upload/uploads/{id}', [MediaUploadController::class, 'show'])
                ->name('customer.media-upload.uploads.show')
                ->middleware('permission:manage_media_uploads');
            Route::put('media-upload/uploads/{id}', [MediaUploadController::class, 'update'])
                ->name('customer.media-upload.uploads.update')
                ->middleware('permission:manage_media_uploads');
            Route::delete('media-upload/uploads/{id}', [MediaUploadController::class, 'destroy'])
                ->name('customer.media-upload.uploads.delete')
                ->middleware('permission:manage_media_uploads');
            Route::get('media-upload/queue', [MediaUploadController::class, 'listQueue'])
                ->name('customer.media-upload.queue')
                ->middleware('permission:upload_media');
            Route::get('media-upload/queue/{id}', [MediaUploadController::class, 'getQueueStatus'])
                ->name('customer.media-upload.queue.show')
                ->middleware('permission:upload_media');
        });
});
