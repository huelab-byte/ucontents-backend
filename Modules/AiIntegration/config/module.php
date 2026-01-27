<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI Integration Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the AI Integration module configuration including
    | module status, features, endpoints, and permissions.
    |
    */

    'name' => 'AiIntegration',
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Module Features
    |--------------------------------------------------------------------------
    |
    | Define features that can be enabled/disabled independently.
    |
    */
    'features' => [
        'api_key_management' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'ai_model_calling' => [
            'enabled' => true,
            'admin_only' => false, // Customers can call AI models
        ],
        'usage_tracking' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'prompt_templates' => [
            'enabled' => true,
            'admin_only' => false, // Customers can use templates
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which endpoints are enabled for admin and customer.
    |
    */
    'endpoints' => [
        'admin' => [
            'providers' => ['enabled' => true],
            'api-keys' => ['enabled' => true],
            'usage' => ['enabled' => true],
            'prompt-templates' => ['enabled' => true],
        ],
        'customer' => [
            'ai-call' => ['enabled' => true],
            'prompt-templates' => ['enabled' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Define permissions for this module.
    |
    */
    'permissions' => [
        'admin' => [
            'manage_ai_providers',
            'manage_ai_api_keys',
            'view_ai_usage',
            'manage_prompt_templates',
        ],
        'customer' => [
            'call_ai_models',
            'use_prompt_templates',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported AI Providers
    |--------------------------------------------------------------------------
    |
    | List of supported AI providers and their configurations.
    |
    */
    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo', 'gpt-3.5-turbo-16k'],
            'vision_models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo'], // Models that support vision
            'embedding_models' => ['text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'],
            'base_url' => 'https://api.openai.com/v1',
        ],
        'azure_openai' => [
            'name' => 'Azure OpenAI',
            'models' => ['gpt-4o', 'gpt-4-turbo', 'gpt-4', 'gpt-35-turbo'],
            'vision_models' => ['gpt-4o', 'gpt-4-turbo'],
            'base_url' => null, // Custom endpoint per API key
        ],
        'anthropic' => [
            'name' => 'Anthropic (Claude)',
            'models' => ['claude-3-5-sonnet-20241022', 'claude-3-opus-20240229', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307'],
            'vision_models' => ['claude-3-5-sonnet-20241022', 'claude-3-opus-20240229', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307'], // All Claude 3 support vision
            'base_url' => 'https://api.anthropic.com/v1',
        ],
        'google' => [
            'name' => 'Google (Gemini)',
            'models' => ['gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-pro', 'gemini-pro-vision'],
            'vision_models' => ['gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-pro-vision'],
            'base_url' => 'https://generativelanguage.googleapis.com/v1',
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'models' => ['deepseek-chat', 'deepseek-coder'],
            'vision_models' => [],
            'base_url' => 'https://api.deepseek.com/v1',
        ],
        'xai' => [
            'name' => 'xAI (Grok)',
            'models' => ['grok-beta', 'grok-2', 'grok-vision-beta'],
            'vision_models' => ['grok-vision-beta'],
            'base_url' => 'https://api.x.ai/v1',
        ],
    ],
];
