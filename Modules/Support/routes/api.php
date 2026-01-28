<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\Api\V1\Admin\SupportTicketController as AdminSupportTicketController;
use Modules\Support\Http\Controllers\Api\V1\Customer\SupportTicketController as CustomerSupportTicketController;
use Modules\Support\Http\Controllers\Api\V1\AttachmentDownloadController;

/*
|--------------------------------------------------------------------------
| Support Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Admin routes
    Route::prefix('admin')
        ->middleware([
            \Modules\Support\Http\Middleware\AcceptTokenFromQuery::class, // Must run before auth:sanctum
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:Support',
        ])
        ->group(function () {
            Route::get('support/tickets', [AdminSupportTicketController::class, 'index'])
                ->middleware('permission:view_all_tickets|manage_tickets')
                ->name('admin.support.tickets.index');
            
            Route::get('support/tickets/{ticket}', [AdminSupportTicketController::class, 'show'])
                ->middleware('permission:view_all_tickets|manage_tickets')
                ->name('admin.support.tickets.show');
            
            Route::post('support/tickets/{ticket}/replies', [AdminSupportTicketController::class, 'reply'])
                ->middleware('permission:manage_tickets')
                ->name('admin.support.tickets.reply');
            
            Route::put('support/tickets/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus'])
                ->middleware('permission:manage_tickets')
                ->name('admin.support.tickets.update-status');
            
            Route::put('support/tickets/{ticket}/assign', [AdminSupportTicketController::class, 'assign'])
                ->middleware('permission:assign_tickets|manage_tickets')
                ->name('admin.support.tickets.assign');
            
            Route::put('support/tickets/{ticket}/priority', [AdminSupportTicketController::class, 'updatePriority'])
                ->middleware('permission:manage_tickets')
                ->name('admin.support.tickets.update-priority');
            
            // Attachment download
            Route::get('support/attachments/{id}/download', [AttachmentDownloadController::class, 'download'])
                ->middleware('permission:view_all_tickets|manage_tickets')
                ->name('admin.support.attachment.download');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            \Modules\Support\Http\Middleware\AcceptTokenFromQuery::class, // Must run before auth:sanctum
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:Support',
        ])
        ->group(function () {
            Route::get('support/tickets', [CustomerSupportTicketController::class, 'index'])
                ->middleware('permission:view_own_tickets|create_tickets')
                ->name('customer.support.tickets.index');
            
            Route::post('support/tickets', [CustomerSupportTicketController::class, 'store'])
                ->middleware('permission:create_tickets')
                ->name('customer.support.tickets.store');
            
            Route::get('support/tickets/{ticket}', [CustomerSupportTicketController::class, 'show'])
                ->middleware('permission:view_own_tickets')
                ->name('customer.support.tickets.show');
            
            Route::post('support/tickets/{ticket}/replies', [CustomerSupportTicketController::class, 'reply'])
                ->middleware('permission:reply_to_own_tickets')
                ->name('customer.support.tickets.reply');
            
            // Attachment download
            Route::get('support/attachments/{id}/download', [AttachmentDownloadController::class, 'download'])
                ->name('customer.support.attachment.download');
        });
});
