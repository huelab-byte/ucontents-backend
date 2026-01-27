<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\Models\AuthenticationSetting;
use Modules\Authentication\Services\AuthenticationSettingsService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Action to update authentication settings
 */
class UpdateAuthSettingsAction
{
    public function __construct(
        private AuthenticationSettingsService $settingsService
    ) {}

    /**
     * Execute the action to update authentication settings
     *
     * @param array $settings The validated settings data
     * @return array The updated settings
     */
    public function execute(array $settings): array
    {
        try {
            // Remove callback_url from provider_configs as it's auto-generated
            if (isset($settings['features']['social_auth']['provider_configs'])) {
                foreach (['google', 'facebook', 'tiktok'] as $provider) {
                    if (isset($settings['features']['social_auth']['provider_configs'][$provider]['callback_url'])) {
                        unset($settings['features']['social_auth']['provider_configs'][$provider]['callback_url']);
                    }
                }
            }

            // Remove null values to prevent overwriting with null
            $settings = $this->removeNullValues($settings);

            // Handle rate limits separately (they're in Client module)
            $rateLimits = $settings['rate_limits'] ?? null;
            unset($settings['rate_limits']);

            // Update settings using the service
            $this->settingsService->update($settings);

            // Clear all caches to ensure fresh data
            AuthenticationSetting::clearCache();

            // Handle rate limits if provided (stored in Client module config)
            if ($rateLimits !== null) {
                $this->updateRateLimits($rateLimits);
            }

            // Return updated settings
            return $this->getFormattedSettings($rateLimits);
        } catch (\Exception $e) {
            Log::error('Failed to update authentication settings', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get formatted settings for API response
     */
    public function getFormattedSettings(?array $rateLimits = null): array
    {
        $allSettings = $this->settingsService->getAll();

        // Get rate limits from Client module config if not provided
        if ($rateLimits === null) {
            $rateLimits = $this->getRateLimits();
        }

        return [
            'features' => $allSettings['features'] ?? [],
            'endpoints' => $allSettings['endpoints'] ?? [],
            'password' => $allSettings['password'] ?? [],
            'token' => $allSettings['token'] ?? [],
            'rate_limits' => $rateLimits,
        ];
    }

    /**
     * Get rate limits from Client module config
     */
    private function getRateLimits(): array
    {
        $defaultLimits = [
            'admin' => ['limit' => 120, 'period' => 60],
            'customer' => ['limit' => 60, 'period' => 60],
            'public' => ['limit' => 30, 'period' => 60],
            'guest' => ['limit' => 10, 'period' => 60],
        ];

        $clientConfigPath = module_path('Client', 'config/module.php');
        if (file_exists($clientConfigPath)) {
            $clientConfig = require $clientConfigPath;
            return $clientConfig['rate_limits'] ?? $defaultLimits;
        }

        return $defaultLimits;
    }

    /**
     * Update rate limits in Client module config
     */
    private function updateRateLimits(array $rateLimits): void
    {
        $clientConfigPath = module_path('Client', 'config/module.php');
        if (!file_exists($clientConfigPath)) {
            return;
        }

        $clientConfig = require $clientConfigPath;
        $clientConfig['rate_limits'] = $rateLimits;

        $content = "<?php\n\n";
        $content .= "declare(strict_types=1);\n\n";
        $content .= "return " . var_export($clientConfig, true) . ";\n";

        if (!File::put($clientConfigPath, $content)) {
            throw new \RuntimeException("Failed to write config file: {$clientConfigPath}");
        }
    }

    /**
     * Remove null values from array recursively
     * Note: We keep false and 0 values as they are valid settings
     */
    private function removeNullValues(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->removeNullValues($value);
                // Remove empty arrays (but keep arrays with false/0 values)
                if (empty($array[$key]) && !in_array(false, $array[$key], true) && !in_array(0, $array[$key], true)) {
                    unset($array[$key]);
                }
            } elseif ($value === null) {
                unset($array[$key]);
            }
            // Keep false, 0, and empty string as they are valid values
        }
        return $array;
    }
}
