<?php

declare(strict_types=1);

namespace Modules\GeneralSettings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for transforming general settings response.
 * 
 * Note: Since GeneralSettings is a key-value store rather than a traditional model,
 * this resource is designed to work with the formatted settings array from 
 * UpdateGeneralSettingsAction::getFormattedSettings().
 */
class GeneralSettingsResource extends JsonResource
{
    /**
     * The resource wraps an array, not an Eloquent model
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // $this->resource is the settings array
        $settings = $this->resource;

        return [
            'branding' => $settings['branding'] ?? [],
            'meta' => $settings['meta'] ?? [],
            'timezone' => $settings['timezone'] ?? config('app.timezone', 'UTC'),
            'contact_email' => $settings['contact_email'] ?? '',
            'support_email' => $settings['support_email'] ?? '',
            'company_name' => $settings['company_name'] ?? '',
            'company_address' => $settings['company_address'] ?? '',
            'social_links' => $settings['social_links'] ?? [],
            'maintenance_mode' => (bool) ($settings['maintenance_mode'] ?? false),
            'terms_of_service_url' => $settings['terms_of_service_url'] ?? '',
            'privacy_policy_url' => $settings['privacy_policy_url'] ?? '',
        ];
    }
}
