<?php

declare(strict_types=1);

return [
    'name' => 'GeneralSettings',
    'enabled' => true,
    
    'features' => [
        'general_settings' => [
            'enabled' => true,
            'admin_only' => true,
        ],
    ],
    
    'endpoints' => [
        'admin' => [
            'general_settings' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => ['manage_general_settings'],
    ],
    
    // Default settings (fallback values)
    'branding' => [
        'site_name' => '',
        'site_description' => '',
        'logo' => '',
        'favicon' => '',
        'primary_color_light' => '#000000',
        'primary_color_dark' => '#ffffff',
    ],
    
    'meta' => [
        'title' => '',
        'description' => '',
        'keywords' => '',
    ],
    
    'timezone' => 'UTC',
    'contact_email' => '',
    'support_email' => '',
    'company_name' => '',
    'company_address' => '',
    
    'social_links' => [
        'facebook' => '',
        'twitter' => '',
        'instagram' => '',
        'linkedin' => '',
        'youtube' => '',
        'tiktok' => '',
    ],
    
    'maintenance_mode' => false,
    'terms_of_service_url' => '',
    'privacy_policy_url' => '',
];
