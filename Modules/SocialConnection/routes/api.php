<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SocialConnection\Http\Controllers\Api\V1\Admin\ProviderAppController;
use Modules\SocialConnection\Http\Controllers\Api\V1\Customer\SocialConnectionController;

Route::prefix('v1')->group(function () {
    // Admin: configure provider apps (no connecting)
    Route::prefix('admin/social-connection')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:SocialConnection',
            'permission:manage_social_connection_providers',
        ])
        ->group(function () {
            Route::get('/providers', [ProviderAppController::class, 'index'])
                ->name('admin.social-connection.providers.index');
            Route::get('/providers/{provider}', [ProviderAppController::class, 'show'])
                ->name('admin.social-connection.providers.show');
            Route::match(['put', 'patch'], '/providers/{provider}', [ProviderAppController::class, 'update'])
                ->name('admin.social-connection.providers.update');
            Route::post('/providers/{provider}/enable', [ProviderAppController::class, 'enable'])
                ->name('admin.social-connection.providers.enable');
            Route::post('/providers/{provider}/disable', [ProviderAppController::class, 'disable'])
                ->name('admin.social-connection.providers.disable');
        });

    // Customer: connect channels (OAuth)
    // Callback must be public (OAuth provider cannot send Bearer token).
    Route::get('customer/social-connection/{provider}/callback', [SocialConnectionController::class, 'callback'])
        ->middleware(['module.feature:SocialConnection'])
        ->name('customer.social-connection.callback');

    Route::prefix('customer/social-connection')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:SocialConnection',
        ])
        ->group(function () {
            Route::get('/providers', [SocialConnectionController::class, 'providers'])
                ->name('customer.social-connection.providers');
            Route::get('/{provider}/redirect', [SocialConnectionController::class, 'redirect'])
                ->name('customer.social-connection.redirect');
            Route::get('/{provider}/available-channels', [SocialConnectionController::class, 'getAvailableChannels'])
                ->name('customer.social-connection.available-channels');
            Route::post('/{provider}/save-selected', [SocialConnectionController::class, 'saveSelectedChannels'])
                ->name('customer.social-connection.save-selected');
            Route::get('/channels', [SocialConnectionController::class, 'channels'])
                ->name('customer.social-connection.channels.index');
            Route::patch('/channels/{channel}/status', [SocialConnectionController::class, 'updateStatus'])
                ->name('customer.social-connection.channels.update-status');
            Route::delete('/channels/{channel}', [SocialConnectionController::class, 'disconnect'])
                ->name('customer.social-connection.channels.disconnect');
            Route::delete('/channels/{channel}/force', [SocialConnectionController::class, 'destroy'])
                ->name('customer.social-connection.channels.destroy');
            Route::patch('/channels/group', [SocialConnectionController::class, 'bulkAssignGroup'])
                ->name('customer.social-connection.channels.bulk-assign-group');
            
            // Groups
            Route::get('/groups', [SocialConnectionController::class, 'indexGroups'])
                ->name('customer.social-connection.groups.index');
            Route::post('/groups', [SocialConnectionController::class, 'storeGroup'])
                ->name('customer.social-connection.groups.store');
            Route::patch('/groups/{group}', [SocialConnectionController::class, 'updateGroup'])
                ->name('customer.social-connection.groups.update');
            Route::delete('/groups/{group}', [SocialConnectionController::class, 'destroyGroup'])
                ->name('customer.social-connection.groups.destroy');
        });
});
