<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PlanManagement\Http\Controllers\Api\V1\Admin\PlanController as AdminPlanController;

/*
|--------------------------------------------------------------------------
| PlanManagement Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public routes - list active plans (no auth)
    Route::prefix('public')->middleware(['throttle:60,1'])->group(function () {
        Route::get('/plans', [\Modules\PlanManagement\Http\Controllers\Api\V1\Public\PlanController::class, 'index'])
            ->name('public.plans.index');
    });

    // Admin routes
    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:PlanManagement',
        ])
        ->group(function () {
            Route::apiResource('plans', AdminPlanController::class)
                ->names('admin.plans')
                ->middleware('permission:view_plans|manage_plans');
            Route::post('plans/{plan}/assign', [AdminPlanController::class, 'assign'])
                ->name('admin.plans.assign')
                ->middleware('permission:manage_plans');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:PlanManagement',
        ])
        ->group(function () {
            Route::get('plans', [\Modules\PlanManagement\Http\Controllers\Api\V1\Customer\PlanController::class, 'index'])
                ->name('customer.plans.index');
            Route::post('plans/{plan}/subscribe', [\Modules\PlanManagement\Http\Controllers\Api\V1\Customer\PlanController::class, 'subscribe'])
                ->middleware('permission:subscribe_to_plan')
                ->name('customer.plans.subscribe');
        });
});
