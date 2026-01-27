<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Admin\InvoiceController as AdminInvoiceController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Admin\InvoiceTemplateController as AdminInvoiceTemplateController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Admin\PaymentGatewayController as AdminPaymentGatewayController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Admin\RefundController as AdminRefundController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Admin\SubscriptionController as AdminSubscriptionController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Customer\InvoiceController as CustomerInvoiceController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Customer\PaymentController as CustomerPaymentController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Customer\RefundController as CustomerRefundController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Customer\SubscriptionController as CustomerSubscriptionController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Webhooks\PayPalWebhookController;
use Modules\PaymentGateway\Http\Controllers\Api\V1\Webhooks\StripeWebhookController;

/*
|--------------------------------------------------------------------------
| PaymentGateway Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the PaymentGateway module.
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
            'module.feature:PaymentGateway'
        ])
        ->group(function () {
            // Payment Gateway Configuration
            Route::apiResource('payment-gateways', AdminPaymentGatewayController::class)
                ->names('admin.payment-gateways');

            // Invoices Management
            Route::apiResource('invoices', AdminInvoiceController::class)
                ->names('admin.invoices');

            // Invoice Templates Management
            Route::apiResource('invoice-templates', AdminInvoiceTemplateController::class)
                ->names('admin.invoice-templates');
            Route::post('invoice-templates/{invoice_template}/set-default', [AdminInvoiceTemplateController::class, 'setDefault'])
                ->name('admin.invoice-templates.set-default');

            // Payments Management
            Route::get('payments', [AdminPaymentController::class, 'index'])
                ->name('admin.payments.index');
            Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])
                ->name('admin.payments.show');

            // Subscriptions Management
            Route::get('subscriptions', [AdminSubscriptionController::class, 'index'])
                ->name('admin.subscriptions.index');
            Route::get('subscriptions/{subscription}', [AdminSubscriptionController::class, 'show'])
                ->name('admin.subscriptions.show');

            // Refunds Management
            Route::apiResource('refunds', AdminRefundController::class)
                ->names('admin.refunds');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:PaymentGateway',
        ])
        ->group(function () {
            // Invoices
            Route::get('invoices', [CustomerInvoiceController::class, 'index'])
                ->name('customer.invoices.index');
            Route::get('invoices/{invoice}', [CustomerInvoiceController::class, 'show'])
                ->name('customer.invoices.show');

            // Payments - read operations
            Route::get('payments', [CustomerPaymentController::class, 'index'])
                ->name('customer.payments.index');
            Route::get('payments/{payment}', [CustomerPaymentController::class, 'show'])
                ->name('customer.payments.show');
            
            // Payments - write operations with rate limiting
            Route::middleware(['throttle:10,1'])->group(function () {
                Route::post('payments', [CustomerPaymentController::class, 'store'])
                    ->name('customer.payments.store');
                Route::put('payments/{payment}', [CustomerPaymentController::class, 'update'])
                    ->name('customer.payments.update');
                Route::delete('payments/{payment}', [CustomerPaymentController::class, 'destroy'])
                    ->name('customer.payments.destroy');
                
                // PayPal payment execution (after user approval)
                Route::post('payments/{payment}/execute-paypal', [CustomerPaymentController::class, 'executePayPal'])
                    ->name('customer.payments.execute-paypal');
            });

            // Subscriptions - read operations
            Route::get('subscriptions', [CustomerSubscriptionController::class, 'index'])
                ->name('customer.subscriptions.index');
            Route::get('subscriptions/{subscription}', [CustomerSubscriptionController::class, 'show'])
                ->name('customer.subscriptions.show');
            
            // Subscriptions - write operations with rate limiting
            Route::middleware(['throttle:10,1'])->group(function () {
                Route::post('subscriptions', [CustomerSubscriptionController::class, 'store'])
                    ->name('customer.subscriptions.store');
                Route::put('subscriptions/{subscription}', [CustomerSubscriptionController::class, 'update'])
                    ->name('customer.subscriptions.update');
                Route::delete('subscriptions/{subscription}', [CustomerSubscriptionController::class, 'destroy'])
                    ->name('customer.subscriptions.destroy');
            });

            // Refunds - read operations
            Route::get('refunds', [CustomerRefundController::class, 'index'])
                ->name('customer.refunds.index');
            Route::get('refunds/{refund}', [CustomerRefundController::class, 'show'])
                ->name('customer.refunds.show');
            
            // Refunds - write operations with rate limiting
            Route::middleware(['throttle:5,1'])->group(function () {
                Route::post('refunds', [CustomerRefundController::class, 'store'])
                    ->name('customer.refunds.store');
            });
        });

    // Webhook routes (public, signature verified)
    Route::prefix('webhooks')
        ->middleware(['throttle:60,1']) // Rate limit webhooks
        ->group(function () {
            // Stripe webhook
            Route::post('stripe', [StripeWebhookController::class, 'handle'])
                ->name('webhooks.stripe');

            // PayPal webhook
            Route::post('paypal', [PayPalWebhookController::class, 'handle'])
                ->name('webhooks.paypal');
        });
});
