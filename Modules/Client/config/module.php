<?php

declare(strict_types=1);

return [
    'name' => 'Client',
    'enabled' => true,

    'features' => [
        'client_management' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'api_key_generation' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'key_rotation' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'activity_logging' => [
            'enabled' => true,
            'admin_only' => true,
        ],
    ],

    'endpoints' => [
        'admin' => [
            'clients' => [
                'enabled' => true,
            ],
            'api_keys' => [
                'enabled' => true,
            ],
        ],
    ],

    'permissions' => [
        'admin' => [
            'manage_clients',
            'generate_api_keys',
            'revoke_api_keys',
            'rotate_api_keys',
            'view_api_key_activity',
        ],
    ],

    'key_generation' => [
        'public_key_length' => 32,
        'secret_key_length' => 64,
        'rotation_required_days' => 90,
    ],

    'rate_limits' => [
        'admin' => [
            'limit' => 120,
            'period' => 60,
        ],
        'customer' => [
            'limit' => 60,
            'period' => 60,
        ],
        'public' => [
            'limit' => 30,
            'period' => 60,
        ],
        'guest' => [
            'limit' => 10,
            'period' => 60,
        ],
    ],
];
