<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Api\V1\AuthController;
use Modules\Authentication\Http\Controllers\Api\V1\PasswordResetController;
use Modules\Authentication\Http\Controllers\Api\V1\SocialAuthController;
use Modules\Authentication\Http\Controllers\Api\V1\TwoFactorController;

/*
|--------------------------------------------------------------------------
| Authentication Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Authentication module.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

Route::prefix('v1')->group(function () {
    // Public endpoint to check enabled auth features
    Route::get('/auth/features', [AuthController::class, 'features'])
        ->name('auth.features');

    // Public authentication routes
    Route::prefix('auth')
        ->middleware(['module.feature:Authentication'])
        ->group(function () {
            // Login/Register
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/register', [AuthController::class, 'register']);

            // Magic Link
            Route::post('/magic-link/request', [AuthController::class, 'requestMagicLink']);
            Route::post('/magic-link/verify', [AuthController::class, 'verifyMagicLink']);

            // OTP
            Route::post('/otp/request', [AuthController::class, 'requestOTP']);
            Route::post('/otp/verify', [AuthController::class, 'verifyOTP']);

            // Password Reset
            Route::post('/password/reset/request', [PasswordResetController::class, 'request'])->name('password.reset.request');
            Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');

            // Email Verification
            Route::post('/email/verify', [\Modules\Authentication\Http\Controllers\Api\V1\EmailVerificationController::class, 'verify']);
            Route::post('/email/resend', [\Modules\Authentication\Http\Controllers\Api\V1\EmailVerificationController::class, 'resend']);

            // Social Authentication - using stateless OAuth (no session required)
            Route::prefix('social')->group(function () {
                Route::get('/{provider}', [SocialAuthController::class, 'redirect'])->where('provider', 'google|facebook|tiktok');
                Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])->where('provider', 'google|facebook|tiktok');
                Route::post('/tiktok/exchange-code', [SocialAuthController::class, 'exchangeCode']);
            });
        });

    // Authenticated routes
    Route::prefix('auth')
        ->middleware(['auth:sanctum', 'module.feature:Authentication'])
        ->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            
            // Two-Factor Authentication - allow access to setup endpoints even if 2FA not enabled
            Route::prefix('2fa')->group(function () {
                Route::get('/status', [TwoFactorController::class, 'status']);
                Route::get('/backup-codes', [TwoFactorController::class, 'backupCodes']);
                Route::post('/setup', [TwoFactorController::class, 'setup']);
                Route::post('/enable', [TwoFactorController::class, 'enable']);
                Route::post('/disable', [TwoFactorController::class, 'disable']);
            });
        });
    
    // Protected routes that require 2FA if configured
    // Apply 2FA check middleware to all other authenticated routes
    Route::middleware([
        'auth:sanctum',
        \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
        'module.feature:Authentication'
    ])->group(function () {
        // Add any other protected routes here that should require 2FA setup
        // For now, this middleware will be applied via route groups in other modules
    });
    
    // Public 2FA verification route (for login)
    Route::prefix('auth/2fa')
        ->middleware(['module.feature:Authentication'])
        ->group(function () {
            Route::post('/verify', [TwoFactorController::class, 'verify']);
        });

    // Admin routes - Authentication Settings (require 2FA if configured)
    Route::prefix('admin/auth-settings')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:Authentication'
        ])
        ->group(function () {
            Route::get('/', [\Modules\Authentication\Http\Controllers\Api\V1\Admin\AuthSettingsController::class, 'index'])
                ->middleware('permission:view_auth_settings|manage_auth_settings');
            Route::put('/', [\Modules\Authentication\Http\Controllers\Api\V1\Admin\AuthSettingsController::class, 'update'])
                ->middleware('permission:update_auth_settings|manage_auth_settings');
            Route::patch('/', [\Modules\Authentication\Http\Controllers\Api\V1\Admin\AuthSettingsController::class, 'update'])
                ->middleware('permission:update_auth_settings|manage_auth_settings');
        });
});
