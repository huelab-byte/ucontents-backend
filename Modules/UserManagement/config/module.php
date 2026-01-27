<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | UserManagement Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the UserManagement module configuration including
    | module status, features, endpoints, and permissions.
    |
    */

    'name' => 'UserManagement',
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
        'user_crud' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'role_management' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'profile_edit' => [
            'enabled' => true,
            'admin_only' => false,
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
            'users' => ['enabled' => true],
            'roles' => ['enabled' => true],
        ],
        'customer' => [
            'profile' => ['enabled' => true],
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
        'admin' => ['manage_users', 'manage_roles'],
        'customer' => ['edit_own_profile'],
    ],
];
