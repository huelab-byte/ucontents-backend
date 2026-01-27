<?php

declare(strict_types=1);

namespace Modules\GeneralSettings\Actions;

use Modules\GeneralSettings\Models\GeneralSetting;
use Modules\GeneralSettings\Services\GeneralSettingsService;
use Illuminate\Support\Facades\Log;

/**
 * Action to update general settings
 */
class UpdateGeneralSettingsAction
{
    public function __construct(
        private GeneralSettingsService $settingsService
    ) {}

    /**
     * Execute the action to update general settings
     *
     * @param array $settings The validated settings data
     * @return array The updated settings
     */
    public function execute(array $settings): array
    {
        try {
            // Remove null values to prevent overwriting with null
            $settings = $this->removeNullValues($settings);

            // Update settings using the service
            $this->settingsService->update($settings);

            // Clear all caches to ensure fresh data
            GeneralSetting::clearCache();

            // Return updated settings
            return $this->getFormattedSettings();
        } catch (\Exception $e) {
            Log::error('Failed to update general settings', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get formatted settings for API response
     */
    public function getFormattedSettings(): array
    {
        $allSettings = $this->settingsService->getAll();

        return [
            'branding' => $allSettings['branding'] ?? [],
            'meta' => $allSettings['meta'] ?? [],
            'timezone' => $allSettings['timezone'] ?? config('app.timezone', 'UTC'),
            'contact_email' => $allSettings['contact_email'] ?? '',
            'support_email' => $allSettings['support_email'] ?? '',
            'company_name' => $allSettings['company_name'] ?? '',
            'company_address' => $allSettings['company_address'] ?? '',
            'social_links' => $allSettings['social_links'] ?? [],
            'maintenance_mode' => $allSettings['maintenance_mode'] ?? false,
            'terms_of_service_url' => $allSettings['terms_of_service_url'] ?? '',
            'privacy_policy_url' => $allSettings['privacy_policy_url'] ?? '',
        ];
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
