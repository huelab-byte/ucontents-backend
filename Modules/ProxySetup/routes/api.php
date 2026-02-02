<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ProxySetup\Http\Controllers\Api\V1\Customer\ProxyController;
use Modules\ProxySetup\Http\Controllers\Api\V1\Customer\ProxySettingsController;

Route::prefix('v1')->group(function () {
    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:ProxySetup',
        ])
        ->group(function () {
            // Proxy Settings
            Route::get('proxy-setup/settings', [ProxySettingsController::class, 'show'])
                ->name('customer.proxy-setup.settings')
                ->middleware('permission:view_proxies');
            
            Route::put('proxy-setup/settings', [ProxySettingsController::class, 'update'])
                ->name('customer.proxy-setup.settings.update')
                ->middleware('permission:manage_proxies');

            // Proxies CRUD
            Route::get('proxy-setup/proxies', [ProxyController::class, 'index'])
                ->name('customer.proxy-setup.proxies')
                ->middleware('permission:view_proxies');
            
            Route::post('proxy-setup/proxies', [ProxyController::class, 'store'])
                ->name('customer.proxy-setup.proxies.store')
                ->middleware('permission:manage_proxies');
            
            Route::get('proxy-setup/proxies/{id}', [ProxyController::class, 'show'])
                ->name('customer.proxy-setup.proxies.show')
                ->middleware('permission:view_proxies');
            
            Route::put('proxy-setup/proxies/{id}', [ProxyController::class, 'update'])
                ->name('customer.proxy-setup.proxies.update')
                ->middleware('permission:manage_proxies');
            
            Route::delete('proxy-setup/proxies/{id}', [ProxyController::class, 'destroy'])
                ->name('customer.proxy-setup.proxies.destroy')
                ->middleware('permission:manage_proxies');
            
            // Proxy enable/disable
            Route::post('proxy-setup/proxies/{id}/enable', [ProxyController::class, 'enable'])
                ->name('customer.proxy-setup.proxies.enable')
                ->middleware('permission:manage_proxies');
            
            Route::post('proxy-setup/proxies/{id}/disable', [ProxyController::class, 'disable'])
                ->name('customer.proxy-setup.proxies.disable')
                ->middleware('permission:manage_proxies');
            
            // Proxy testing
            Route::post('proxy-setup/proxies/{id}/test', [ProxyController::class, 'test'])
                ->name('customer.proxy-setup.proxies.test')
                ->middleware('permission:view_proxies');
            
            // Channel assignment
            Route::post('proxy-setup/proxies/{id}/assign-channels', [ProxyController::class, 'assignChannels'])
                ->name('customer.proxy-setup.proxies.assign-channels')
                ->middleware('permission:manage_proxies');
        });
});
