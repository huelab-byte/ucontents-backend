<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Client\Http\Controllers\Api\V1\Admin\ApiClientController;
use Modules\Client\Http\Controllers\Api\V1\Admin\ApiKeyController;

/*
|--------------------------------------------------------------------------
| Client Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Client module.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

Route::prefix('v1')->group(function () {
    // Admin routes - API Client Management
    Route::prefix('admin/clients')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:Client'
        ])
        ->group(function () {
            // Client CRUD
            Route::get('/', [ApiClientController::class, 'index'])
                ->middleware('permission:view_clients|manage_clients')
                ->name('admin.clients.index');
            Route::post('/', [ApiClientController::class, 'store'])
                ->middleware('permission:create_client|manage_clients')
                ->name('admin.clients.store');
            Route::get('/{apiClient}', [ApiClientController::class, 'show'])
                ->middleware('permission:view_clients|manage_clients')
                ->name('admin.clients.show');
            Route::put('/{apiClient}', [ApiClientController::class, 'update'])
                ->middleware('permission:update_client|manage_clients')
                ->name('admin.clients.update');
            Route::patch('/{apiClient}', [ApiClientController::class, 'update'])
                ->middleware('permission:update_client|manage_clients')
                ->name('admin.clients.patch');
            Route::delete('/{apiClient}', [ApiClientController::class, 'destroy'])
                ->middleware('permission:delete_client|manage_clients')
                ->name('admin.clients.destroy');

            // API Keys management
            Route::prefix('{apiClient}/keys')->group(function () {
                Route::get('/', [ApiKeyController::class, 'index'])
                    ->middleware('permission:view_clients|manage_clients')
                    ->name('admin.clients.keys.index');
                Route::post('/', [ApiKeyController::class, 'store'])
                    ->middleware('permission:generate_api_keys|manage_clients')
                    ->name('admin.clients.keys.store');
                Route::get('/{apiKey}', [ApiKeyController::class, 'show'])
                    ->middleware('permission:view_clients|manage_clients')
                    ->name('admin.clients.keys.show');
                Route::post('/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])
                    ->middleware('permission:revoke_api_keys|manage_clients')
                    ->name('admin.clients.keys.revoke');
                Route::post('/{apiKey}/rotate', [ApiKeyController::class, 'rotate'])
                    ->middleware('permission:rotate_api_keys|manage_clients')
                    ->name('admin.clients.keys.rotate');
                Route::get('/{apiKey}/activity', [ApiKeyController::class, 'activityLogs'])
                    ->middleware('permission:view_api_key_activity|manage_clients')
                    ->name('admin.clients.keys.activity');
            });
        });
});
