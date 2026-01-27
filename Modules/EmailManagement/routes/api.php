<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\EmailManagement\Http\Controllers\Api\V1\Admin\EmailTemplateController;
use Modules\EmailManagement\Http\Controllers\Api\V1\Admin\EmailTestController;
use Modules\EmailManagement\Http\Controllers\Api\V1\Admin\SmtpConfigurationController;

/*
|--------------------------------------------------------------------------
| Email Management Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Email Management module.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

Route::prefix('v1')->group(function () {
    // Admin routes
    Route::prefix('admin/email-management')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:EmailManagement'
        ])
        ->name('admin.email.')
        ->group(function () {
            // SMTP Configuration routes
            Route::get('smtp-configurations', [SmtpConfigurationController::class, 'index'])
                ->name('smtp-configurations.index')
                ->middleware('permission:view_email_config|manage_email_config');
            Route::post('smtp-configurations', [SmtpConfigurationController::class, 'store'])
                ->name('smtp-configurations.store')
                ->middleware('permission:update_email_config|manage_email_config');
            Route::get('smtp-configurations/{smtp_configuration}', [SmtpConfigurationController::class, 'show'])
                ->name('smtp-configurations.show')
                ->middleware('permission:view_email_config|manage_email_config');
            Route::put('smtp-configurations/{smtp_configuration}', [SmtpConfigurationController::class, 'update'])
                ->name('smtp-configurations.update')
                ->middleware('permission:update_email_config|manage_email_config');
            Route::patch('smtp-configurations/{smtp_configuration}', [SmtpConfigurationController::class, 'update'])
                ->name('smtp-configurations.patch')
                ->middleware('permission:update_email_config|manage_email_config');
            Route::delete('smtp-configurations/{smtp_configuration}', [SmtpConfigurationController::class, 'destroy'])
                ->name('smtp-configurations.destroy')
                ->middleware('permission:update_email_config|manage_email_config');
            Route::post('smtp-configurations/{smtp_configuration}/set-default', [SmtpConfigurationController::class, 'setDefault'])
                ->name('smtp-configurations.set-default')
                ->middleware('permission:update_email_config|manage_email_config');

            // Email Template routes
            Route::get('email-templates', [EmailTemplateController::class, 'index'])
                ->name('templates.index')
                ->middleware('permission:view_email_templates|manage_email_templates');
            Route::post('email-templates', [EmailTemplateController::class, 'store'])
                ->name('templates.store')
                ->middleware('permission:create_email_template|manage_email_templates');
            Route::get('email-templates/{email_template}', [EmailTemplateController::class, 'show'])
                ->name('templates.show')
                ->middleware('permission:view_email_templates|manage_email_templates');
            Route::put('email-templates/{email_template}', [EmailTemplateController::class, 'update'])
                ->name('templates.update')
                ->middleware('permission:update_email_template|manage_email_templates');
            Route::patch('email-templates/{email_template}', [EmailTemplateController::class, 'update'])
                ->name('templates.patch')
                ->middleware('permission:update_email_template|manage_email_templates');
            Route::delete('email-templates/{email_template}', [EmailTemplateController::class, 'destroy'])
                ->name('templates.destroy')
                ->middleware('permission:delete_email_template|manage_email_templates');

            // Test email route
            Route::post('test-email', [EmailTestController::class, 'sendTest'])
                ->name('test-email')
                ->middleware('permission:send_test_email|manage_email_config');
        });
});
