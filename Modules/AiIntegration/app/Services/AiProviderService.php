<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Services;

use Modules\AiIntegration\Models\AiProvider;

/**
 * Service for managing AI providers
 */
class AiProviderService
{
    /**
     * Initialize providers from config
     */
    public function initializeProviders(): void
    {
        // The config is registered as 'aiintegration.module' for config/module.php
        $providers = config('aiintegration.module.providers', []);

        if (empty($providers)) {
            \Log::warning('No providers found in config. Check aiintegration.module.providers');
            return;
        }

        foreach ($providers as $slug => $config) {
            AiProvider::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'],
                    'supported_models' => $config['models'] ?? [],
                    'base_url' => $config['base_url'] ?? null,
                    'is_active' => true,
                    'config' => $config,
                ]
            );
        }
    }

    /**
     * Get all active providers
     */
    public function getActiveProviders()
    {
        return AiProvider::where('is_active', true)->get();
    }

    /**
     * Get provider by slug
     */
    public function getProviderBySlug(string $slug): ?AiProvider
    {
        return AiProvider::where('slug', $slug)->where('is_active', true)->first();
    }
}
