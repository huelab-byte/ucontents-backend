<?php

declare(strict_types=1);

return [
    'name' => 'SocialConnection',
    'enabled' => true,

    // Future-friendly feature flags (can be wired to settings DB later)
    'features' => [
        'providers' => [
            'enabled' => true,
        ],
        'customer_connect' => [
            'enabled' => true,
        ],
    ],
];

