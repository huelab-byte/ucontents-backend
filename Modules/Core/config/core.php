<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Core Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the core module configuration including
    | module status, permissions, and system-wide settings.
    |
    */

    'name' => 'Core',
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | System Permissions
    |--------------------------------------------------------------------------
    |
    | Define system-wide permissions that can be used across all modules.
    |
    */
    'permissions' => [
        'admin' => [
            'manage_users',
            'manage_roles',
            'manage_permissions',
            'manage_modules',
            'view_analytics',
            'manage_settings',
        ],
        'customer' => [
            'view_own_profile',
            'edit_own_profile',
        ],
    ],
];
