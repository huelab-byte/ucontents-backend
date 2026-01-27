<?php

declare(strict_types=1);

return [
    'name' => 'Core',
    'enabled' => true,

    'features' => [
        // Core module provides base functionality for all other modules
    ],

    'endpoints' => [
        'admin' => [
            // Admin-only endpoints will be added here
        ],
        'customer' => [
            // Customer endpoints will be added here
        ],
    ],

    'permissions' => [
        'admin' => [],
        'customer' => [],
    ],
];
