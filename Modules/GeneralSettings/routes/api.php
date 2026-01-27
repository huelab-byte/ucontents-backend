<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\GeneralSettings\Http\Controllers\Api\V1\Admin\GeneralSettingsController;
use Modules\GeneralSettings\Http\Controllers\Api\V1\Public\GeneralSettingsController as PublicGeneralSettingsController;

/*
|--------------------------------------------------------------------------
| General Settings Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the General Settings module.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

Route::prefix('v1')->group(function () {
    // Public routes - General Settings (for site metadata)
    Route::get('/general-settings', [PublicGeneralSettingsController::class, 'index'])
        ->name('public.general-settings.index');
    
    // Admin routes - General Settings
    Route::prefix('admin/general-settings')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:GeneralSettings'
        ])
        ->name('admin.general-settings.')
        ->group(function () {
            Route::get('/', [GeneralSettingsController::class, 'index'])
                ->name('index')
                ->middleware('permission:view_general_settings|manage_general_settings');
            Route::put('/', [GeneralSettingsController::class, 'update'])
                ->name('update')
                ->middleware('permission:update_general_settings|manage_general_settings');
            Route::patch('/', [GeneralSettingsController::class, 'update'])
                ->name('patch')
                ->middleware('permission:update_general_settings|manage_general_settings');
        });
});
