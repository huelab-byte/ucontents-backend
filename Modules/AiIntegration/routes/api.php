<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AiIntegration\Http\Controllers\Api\V1\Admin\AiApiKeyController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Admin\AiProviderController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Admin\AiPromptTemplateController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Admin\AiUsageController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Customer\AiModelController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Customer\AiPromptTemplateController as CustomerAiPromptTemplateController;

/*
|--------------------------------------------------------------------------
| AI Integration Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the AI Integration module.
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
            'module.feature:AiIntegration'
        ])
        ->group(function () {
            // AI Providers
            Route::prefix('ai-providers')->group(function () {
                Route::get('/', [AiProviderController::class, 'index'])
                    ->middleware('permission:manage_ai_providers')
                    ->name('admin.ai-providers.index');
                Route::get('/{provider}', [AiProviderController::class, 'show'])
                    ->middleware('permission:manage_ai_providers')
                    ->name('admin.ai-providers.show');
                Route::post('/initialize', [AiProviderController::class, 'initialize'])
                    ->middleware('permission:manage_ai_providers')
                    ->name('admin.ai-providers.initialize');
            });

            // AI API Keys
            Route::prefix('ai-api-keys')->group(function () {
                Route::get('/', [AiApiKeyController::class, 'index'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.index');
                Route::post('/', [AiApiKeyController::class, 'store'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.store');
                Route::get('/{apiKey}', [AiApiKeyController::class, 'show'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.show');
                Route::put('/{apiKey}', [AiApiKeyController::class, 'update'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.update');
                Route::patch('/{apiKey}', [AiApiKeyController::class, 'update'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.patch');
                Route::delete('/{apiKey}', [AiApiKeyController::class, 'destroy'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.destroy');
                Route::post('/{apiKey}/enable', [AiApiKeyController::class, 'enable'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.enable');
                Route::post('/{apiKey}/disable', [AiApiKeyController::class, 'disable'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.disable');
            });

            // AI Usage & Statistics
            Route::prefix('ai-usage')->group(function () {
                Route::get('/', [AiUsageController::class, 'index'])
                    ->middleware('permission:view_ai_usage')
                    ->name('admin.ai-usage.index');
                Route::get('/statistics', [AiUsageController::class, 'statistics'])
                    ->middleware('permission:view_ai_usage')
                    ->name('admin.ai-usage.statistics');
            });

            // Prompt Templates
            Route::prefix('ai-prompt-templates')->group(function () {
                Route::get('/', [AiPromptTemplateController::class, 'index'])
                    ->middleware('permission:manage_prompt_templates')
                    ->name('admin.ai-prompt-templates.index');
                Route::post('/', [AiPromptTemplateController::class, 'store'])
                    ->middleware('permission:manage_prompt_templates')
                    ->name('admin.ai-prompt-templates.store');
                Route::get('/{promptTemplate}', [AiPromptTemplateController::class, 'show'])
                    ->middleware('permission:manage_prompt_templates')
                    ->name('admin.ai-prompt-templates.show');
                Route::put('/{promptTemplate}', [AiPromptTemplateController::class, 'update'])
                    ->middleware('permission:manage_prompt_templates')
                    ->name('admin.ai-prompt-templates.update');
                Route::patch('/{promptTemplate}', [AiPromptTemplateController::class, 'update'])
                    ->middleware('permission:manage_prompt_templates')
                    ->name('admin.ai-prompt-templates.patch');
                Route::delete('/{promptTemplate}', [AiPromptTemplateController::class, 'destroy'])
                    ->middleware('permission:manage_prompt_templates')
                    ->name('admin.ai-prompt-templates.destroy');
            });
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:AiIntegration',
        ])
        ->group(function () {
            // AI Model Calls
            Route::prefix('ai')->group(function () {
                Route::post('/call', [AiModelController::class, 'call'])
                    ->middleware('permission:call_ai_models')
                    ->name('customer.ai.call');
            });

            // Prompt Templates (read-only for customers)
            Route::prefix('ai-prompt-templates')->group(function () {
                Route::get('/', [CustomerAiPromptTemplateController::class, 'index'])
                    ->middleware('permission:use_prompt_templates')
                    ->name('customer.ai-prompt-templates.index');
                Route::get('/{promptTemplate}', [CustomerAiPromptTemplateController::class, 'show'])
                    ->middleware('permission:use_prompt_templates')
                    ->name('customer.ai-prompt-templates.show');
                Route::post('/{promptTemplate}/render', [CustomerAiPromptTemplateController::class, 'render'])
                    ->middleware('permission:use_prompt_templates')
                    ->name('customer.ai-prompt-templates.render');
            });
        });
});
