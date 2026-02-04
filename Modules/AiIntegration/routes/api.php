<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AiIntegration\Http\Controllers\Api\V1\Admin\AiApiKeyController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Admin\AiProviderController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Admin\AiPromptTemplateController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Admin\AiUsageController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Customer\AiChatController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Customer\AiModelController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Customer\AiPromptTemplateController as CustomerAiPromptTemplateController;
use Modules\AiIntegration\Http\Controllers\Api\V1\Customer\AiApiKeyController as CustomerAiApiKeyController;

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
                Route::post('/initialize', [AiProviderController::class, 'initialize'])
                    ->middleware('permission:manage_ai_providers')
                    ->name('admin.ai-providers.initialize');
                Route::get('/{provider}', [AiProviderController::class, 'show'])
                    ->middleware('permission:manage_ai_providers')
                    ->name('admin.ai-providers.show')
                    ->whereNumber('provider');
            });

            // AI API Keys
            Route::prefix('ai-api-keys')->group(function () {
                Route::get('/', [AiApiKeyController::class, 'index'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.index');
                Route::get('/scopes', [AiApiKeyController::class, 'scopes'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.scopes');
                Route::post('/', [AiApiKeyController::class, 'store'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.store');
                Route::get('/{apiKey}', [AiApiKeyController::class, 'show'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.show')
                    ->whereNumber('apiKey');
                Route::put('/{apiKey}', [AiApiKeyController::class, 'update'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.update')
                    ->whereNumber('apiKey');
                Route::patch('/{apiKey}', [AiApiKeyController::class, 'update'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.patch')
                    ->whereNumber('apiKey');
                Route::delete('/{apiKey}', [AiApiKeyController::class, 'destroy'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.destroy')
                    ->whereNumber('apiKey');
                Route::post('/{apiKey}/enable', [AiApiKeyController::class, 'enable'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.enable')
                    ->whereNumber('apiKey');
                Route::post('/{apiKey}/disable', [AiApiKeyController::class, 'disable'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.disable')
                    ->whereNumber('apiKey');
                Route::post('/{apiKey}/test', [AiApiKeyController::class, 'test'])
                    ->middleware('permission:manage_ai_api_keys')
                    ->name('admin.ai-api-keys.test')
                    ->whereNumber('apiKey');
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

            // AI Chat (External AI Service)
            Route::prefix('ai-chat')->group(function () {
                Route::post('/', [AiChatController::class, 'chat'])
                    ->middleware('permission:use_ai_chat')
                    ->name('customer.ai-chat.chat');
                Route::post('/analyze-image', [AiChatController::class, 'analyzeImage'])
                    ->middleware('permission:use_ai_chat')
                    ->name('customer.ai-chat.analyze-image');
                Route::get('/test', [AiChatController::class, 'testConnection'])
                    ->middleware('permission:use_ai_chat')
                    ->name('customer.ai-chat.test');
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

            // Customer AI API Keys
            Route::prefix('ai-api-keys')->group(function () {
                Route::get('/', [CustomerAiApiKeyController::class, 'index'])->name('customer.ai-api-keys.index');
                Route::post('/', [CustomerAiApiKeyController::class, 'store'])->name('customer.ai-api-keys.store');
                Route::get('/providers', [CustomerAiApiKeyController::class, 'providers'])->name('customer.ai-api-keys.providers');
                Route::get('/scopes', [CustomerAiApiKeyController::class, 'scopes'])->name('customer.ai-api-keys.scopes');
                Route::get('/{apiKey}', [CustomerAiApiKeyController::class, 'show'])->name('customer.ai-api-keys.show')->whereNumber('apiKey');
                Route::put('/{apiKey}', [CustomerAiApiKeyController::class, 'update'])->name('customer.ai-api-keys.update')->whereNumber('apiKey');
                Route::delete('/{apiKey}', [CustomerAiApiKeyController::class, 'destroy'])->name('customer.ai-api-keys.destroy')->whereNumber('apiKey');
                Route::post('/{apiKey}/test', [CustomerAiApiKeyController::class, 'test'])->name('customer.ai-api-keys.test')->whereNumber('apiKey');
            });
        });
});
