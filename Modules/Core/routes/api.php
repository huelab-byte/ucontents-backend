<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Core module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Health check endpoint (public)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'System is healthy',
        'timestamp' => now()->toISOString(),
    ]);
})->name('health');

// Version 1 API routes
Route::prefix('v1')->group(function () {
    // Public routes (no authentication required)
    // Add public routes here if needed

    // Admin routes
    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:core'
        ])
        ->group(function () {
            // Admin-only endpoints will be added here
        });

    // Customer routes (require 2FA if configured)
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:core'
        ])
        ->group(function () {
            // Customer endpoints will be added here
        });
});
