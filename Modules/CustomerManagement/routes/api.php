<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CustomerManagement\Http\Controllers\Api\V1\Admin\CustomerController;

/*
|--------------------------------------------------------------------------
| CustomerManagement Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:CustomerManagement',
        ])
        ->group(function () {
            Route::get('customers', [CustomerController::class, 'index'])
                ->middleware('permission:view_customers|manage_customers')
                ->name('admin.customers.index');
            Route::get('customers/{user}', [CustomerController::class, 'show'])
                ->middleware('permission:view_customers|manage_customers')
                ->name('admin.customers.show');
        });
});
