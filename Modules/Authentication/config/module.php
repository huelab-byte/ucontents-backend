<?php

declare(strict_types=1);

return [
    'name' => 'Authentication',
    'enabled' => true,

    'features' => [
        'email_verification' => [
            'enabled' => true,
            'required' => true,
        ],
        'password_reset' => [
            'enabled' => true,
            'token_expiry' => 60,
        ],
        'magic_link' => [
            'enabled' => false,
            'token_expiry' => 15,
            'rate_limit' => 3,
        ],
        'otp_2fa' => [
            'enabled' => true,
            'required_for_admin' => false,
            'required_for_customer' => true,
        ],
        'social_auth' => [
            'enabled' => false,
            'providers' => [
                'google',
                'facebook',
                'tiktok',
            ],
            'provider_configs' => [
                'google' => [
                    'client_id' => null,
                    'client_secret' => null,
                ],
                'facebook' => [
                    'client_id' => null,
                    'client_secret' => null,
                ],
                'tiktok' => [
                    'client_id' => null,
                    'client_secret' => null,
                ],
            ],
        ],
    ],

    'endpoints' => [
        'public' => [
            'login' => [
                'enabled' => true,
            ],
            'register' => [
                'enabled' => true,
            ],
            'password_reset' => [
                'enabled' => true,
            ],
            'email_verification' => [
                'enabled' => true,
            ],
            'magic_link' => [
                'enabled' => true,
            ],
            'otp' => [
                'enabled' => true,
            ],
            'social_auth' => [
                'enabled' => true,
            ],
        ],
        'customer' => [
            'logout' => [
                'enabled' => true,
            ],
            'refresh_token' => [
                'enabled' => true,
            ],
        ],
    ],

    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_number' => true,
        'require_special' => true,
    ],

    'token' => [
        'sanctum_expiry' => 1440,
        'jwt_expiry' => 60,
        'refresh_expiry' => 43200,
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
