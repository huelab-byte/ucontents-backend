<?php

declare(strict_types=1);

namespace Modules\GeneralSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for updating general settings
 */
class UpdateGeneralSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Note: The route already has 'admin' middleware which checks for admin/super_admin roles.
     */
    public function authorize(): bool
    {
        // The route middleware already checks for admin access via isAdmin()
        // which checks for both 'admin' and 'super_admin' roles
        // We just need to ensure the user is authenticated
        $user = $this->user();
        return $user !== null && $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Core Branding (Required)
            'branding' => 'nullable|array',
            'branding.site_name' => 'nullable|string|max:255',
            'branding.site_description' => 'nullable|string|max:500',
            'branding.logo' => 'nullable|string|max:500',
            'branding.favicon' => 'nullable|string|max:500',
            'branding.site_icon' => 'nullable|string|max:500',
            'branding.primary_color_light' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'branding.primary_color_dark' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            
            // Meta Tags (SEO)
            'meta' => 'nullable|array',
            'meta.title' => 'nullable|string|max:255',
            'meta.description' => 'nullable|string|max:500',
            'meta.keywords' => 'nullable|string|max:500',
            
            // Additional Configurations
            'timezone' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email|max:255',
            'support_email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:500',
            
            // Social Media Links (Optional)
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:500',
            'social_links.twitter' => 'nullable|url|max:500',
            'social_links.instagram' => 'nullable|url|max:500',
            'social_links.linkedin' => 'nullable|url|max:500',
            'social_links.youtube' => 'nullable|url|max:500',
            'social_links.tiktok' => 'nullable|url|max:500',
            
            // Maintenance & Legal
            'maintenance_mode' => 'nullable|boolean',
            'terms_of_service_url' => 'nullable|url|max:500',
            'privacy_policy_url' => 'nullable|url|max:500',
        ];
    }
}
