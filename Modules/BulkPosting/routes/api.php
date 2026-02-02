<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\BulkPosting\Http\Controllers\Api\V1\Customer\CampaignController;

Route::prefix('v1')->group(function () {
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:BulkPosting',
        ])
        ->group(function () {
            Route::get('bulk-posting/campaigns', [CampaignController::class, 'index'])
                ->name('customer.bulk-posting.campaigns.index')
                ->middleware('permission:view_bulk_posting_campaigns');

            Route::post('bulk-posting/campaigns', [CampaignController::class, 'store'])
                ->name('customer.bulk-posting.campaigns.store')
                ->middleware('permission:manage_bulk_posting_campaigns');

            Route::get('bulk-posting/campaigns/{id}', [CampaignController::class, 'show'])
                ->name('customer.bulk-posting.campaigns.show')
                ->middleware('permission:view_bulk_posting_campaigns');

            Route::put('bulk-posting/campaigns/{id}', [CampaignController::class, 'update'])
                ->name('customer.bulk-posting.campaigns.update')
                ->middleware('permission:manage_bulk_posting_campaigns');

            Route::delete('bulk-posting/campaigns/{id}', [CampaignController::class, 'destroy'])
                ->name('customer.bulk-posting.campaigns.destroy')
                ->middleware('permission:manage_bulk_posting_campaigns');

            Route::post('bulk-posting/campaigns/{id}/pause', [CampaignController::class, 'pause'])
                ->name('customer.bulk-posting.campaigns.pause')
                ->middleware('permission:manage_bulk_posting_campaigns');

            Route::post('bulk-posting/campaigns/{id}/resume', [CampaignController::class, 'resume'])
                ->name('customer.bulk-posting.campaigns.resume')
                ->middleware('permission:manage_bulk_posting_campaigns');

            Route::post('bulk-posting/campaigns/{id}/start', [CampaignController::class, 'start'])
                ->name('customer.bulk-posting.campaigns.start')
                ->middleware('permission:manage_bulk_posting_campaigns');

            Route::get('bulk-posting/campaigns/{id}/content-items', [CampaignController::class, 'contentItems'])
                ->name('customer.bulk-posting.campaigns.content-items')
                ->middleware('permission:view_bulk_posting_campaigns');

            Route::post('bulk-posting/campaigns/{id}/sync', [CampaignController::class, 'sync'])
                ->name('customer.bulk-posting.campaigns.sync')
                ->middleware('permission:manage_bulk_posting_campaigns');

            Route::get('bulk-posting/sample-csv', [CampaignController::class, 'downloadSampleCsv'])
                ->name('customer.bulk-posting.sample-csv')
                ->middleware('permission:view_bulk_posting_campaigns');
        });
});
