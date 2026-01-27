<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\Api\V1\Admin\PermissionController;
use Modules\UserManagement\Http\Controllers\Api\V1\Admin\RoleController;
use Modules\UserManagement\Http\Controllers\Api\V1\Admin\UserController;
use Modules\UserManagement\Http\Controllers\Api\V1\Customer\ProfileController;

/*
|--------------------------------------------------------------------------
| UserManagement Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the UserManagement module.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

Route::prefix('v1')->group(function () {
    // Admin routes
    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:UserManagement'
        ])
        ->name('admin.')
        ->group(function () {
            // User management
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:view_users|manage_users');
                Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:create_user|manage_users');
                Route::get('/{user}', [UserController::class, 'show'])->name('show')->middleware('permission:view_users|manage_users');
                Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:update_user|manage_users');
                Route::patch('/{user}', [UserController::class, 'update'])->name('patch')->middleware('permission:update_user|manage_users');
                Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:delete_user|manage_users');
            });

            // Role management
            Route::prefix('roles')->name('roles.')->group(function () {
                Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('permission:view_roles|manage_roles');
                Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('permission:create_role|manage_roles');
                Route::get('/permissions/list', [RoleController::class, 'permissions'])->name('permissions')->middleware('permission:view_roles|manage_roles');
                Route::get('/{role}', [RoleController::class, 'show'])->name('show')->middleware('permission:view_roles|manage_roles');
                Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('permission:update_role|manage_roles');
                Route::patch('/{role}', [RoleController::class, 'update'])->name('patch')->middleware('permission:update_role|manage_roles');
                Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('permission:delete_role|manage_roles');
            });

            // Permission management
            Route::prefix('permissions')->name('permissions.')->group(function () {
                Route::get('/', [PermissionController::class, 'index'])->name('index')->middleware('permission:view_permissions|manage_permissions');
                Route::post('/', [PermissionController::class, 'store'])->name('store')->middleware('permission:create_permission|manage_permissions');
                Route::get('/modules', [PermissionController::class, 'modules'])->name('modules')->middleware('permission:view_permissions|manage_permissions');
                Route::get('/{permission}', [PermissionController::class, 'show'])->name('show')->middleware('permission:view_permissions|manage_permissions');
                Route::put('/{permission}', [PermissionController::class, 'update'])->name('update')->middleware('permission:update_permission|manage_permissions');
                Route::patch('/{permission}', [PermissionController::class, 'update'])->name('patch')->middleware('permission:update_permission|manage_permissions');
                Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy')->middleware('permission:delete_permission|manage_permissions');
            });
        });

    // Customer routes (require 2FA if configured)
    // Note: Profile routes don't need permission checks - they're self-service
    // routes where users can only access their own data
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:UserManagement'
        ])
        ->name('customer.')
        ->group(function () {
            // Profile management - users can manage their own profile
            // No permission checks needed - these are self-service routes
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/', [ProfileController::class, 'show'])->name('show');
                Route::put('/', [ProfileController::class, 'update'])->name('update');
                Route::patch('/', [ProfileController::class, 'update'])->name('patch');
            });
        });
});
