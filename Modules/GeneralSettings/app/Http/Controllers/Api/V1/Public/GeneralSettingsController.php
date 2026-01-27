<?php

declare(strict_types=1);

namespace Modules\GeneralSettings\Http\Controllers\Api\V1\Public;

use Illuminate\Http\JsonResponse;
use Modules\GeneralSettings\Services\GeneralSettingsService;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Public API Controller for accessing general settings
 * No authentication required - used for public site metadata
 */
class GeneralSettingsController extends BaseApiController
{
    /**
     * Get public general settings (for site metadata, branding, etc.)
     */
    public function index(): JsonResponse
    {
        $settingsService = app(GeneralSettingsService::class);
        $allSettings = $settingsService->getAll();
        
        // Only return public-facing settings (exclude sensitive info like emails)
        return $this->success([
            'branding' => [
                'site_name' => $allSettings['branding']['site_name'] ?? '',
                'site_description' => $allSettings['branding']['site_description'] ?? '',
                'logo' => $allSettings['branding']['logo'] ?? '',
                'favicon' => $allSettings['branding']['favicon'] ?? '',
                'site_icon' => $allSettings['branding']['site_icon'] ?? '',
                'primary_color_light' => $allSettings['branding']['primary_color_light'] ?? '#000000',
                'primary_color_dark' => $allSettings['branding']['primary_color_dark'] ?? '#ffffff',
            ],
            'meta' => [
                'title' => $allSettings['meta']['title'] ?? '',
                'description' => $allSettings['meta']['description'] ?? '',
                'keywords' => $allSettings['meta']['keywords'] ?? '',
            ],
            'social_links' => $allSettings['social_links'] ?? [],
            'maintenance_mode' => $allSettings['maintenance_mode'] ?? false,
            'terms_of_service_url' => $allSettings['terms_of_service_url'] ?? '',
            'privacy_policy_url' => $allSettings['privacy_policy_url'] ?? '',
        ], 'General settings retrieved successfully');
    }
}
