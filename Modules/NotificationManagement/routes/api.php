<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\NotificationManagement\Http\Controllers\Api\V1\Admin\AnnouncementController;
use Modules\NotificationManagement\Http\Controllers\Api\V1\Admin\NotificationSettingsController;
use Modules\NotificationManagement\Http\Controllers\Api\V1\Admin\PusherAuthController as AdminPusherAuthController;
use Modules\NotificationManagement\Http\Controllers\Api\V1\Customer\NotificationController;
use Modules\NotificationManagement\Http\Controllers\Api\V1\Customer\PusherAuthController as CustomerPusherAuthController;
use Modules\NotificationManagement\Http\Controllers\Api\V1\PusherConfigController;

/*
|--------------------------------------------------------------------------
| NotificationManagement Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Pusher config endpoint (accessible to any authenticated user)
    Route::get('pusher/config', [PusherConfigController::class, 'index'])
        ->middleware(['auth:sanctum', 'module.feature:NotificationManagement'])
        ->name('pusher.config');

    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:NotificationManagement',
        ])
        ->group(function () {
            Route::get('announcements', [AnnouncementController::class, 'index'])
                ->middleware('permission:view_admin_notifications|manage_announcements|manage_settings')
                ->name('admin.announcements.index');

            Route::post('announcements', [AnnouncementController::class, 'store'])
                ->middleware('permission:manage_announcements|manage_settings')
                ->name('admin.announcements.store');

            Route::post('notifications/pusher/auth', [AdminPusherAuthController::class, 'auth'])
                ->middleware('permission:view_admin_notifications|manage_announcements|manage_settings')
                ->name('admin.notifications.pusher.auth');

            Route::get('notification-settings', [NotificationSettingsController::class, 'index'])
                ->middleware('permission:view_notification_settings|manage_notification_settings')
                ->name('admin.notification-settings.index');

            Route::put('notification-settings', [NotificationSettingsController::class, 'update'])
                ->middleware('permission:manage_notification_settings')
                ->name('admin.notification-settings.update');
        });

    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:NotificationManagement',
        ])
        ->group(function () {
            Route::get('notifications', [NotificationController::class, 'index'])
                ->middleware('permission:view_notifications|manage_settings')
                ->name('customer.notifications.index');

            Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])
                ->middleware('permission:view_notifications|manage_settings')
                ->name('customer.notifications.unread-count');

            Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])
                ->middleware('permission:view_notifications|manage_settings')
                ->name('customer.notifications.mark-all-read');

            Route::delete('notifications', [NotificationController::class, 'clearAll'])
                ->middleware('permission:view_notifications|manage_settings')
                ->name('customer.notifications.clear-all');

            Route::post('notifications/{recipient}/read', [NotificationController::class, 'markRead'])
                ->middleware('permission:view_notifications|manage_settings')
                ->name('customer.notifications.mark-read');

            Route::post('notifications/pusher/auth', [CustomerPusherAuthController::class, 'auth'])
                ->middleware('permission:view_notifications|manage_settings')
                ->name('customer.notifications.pusher.auth');
        });
});
