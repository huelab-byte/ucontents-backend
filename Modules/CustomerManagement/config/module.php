<?php

declare(strict_types=1);

return [
    'name' => 'CustomerManagement',
    'enabled' => true,

    'features' => [
        'customers' => [
            'enabled' => true,
            'admin_only' => true,
        ],
    ],

    'endpoints' => [
        'admin' => [
            'customers' => ['enabled' => true],
        ],
    ],

    'permissions' => [
        'admin' => [
            'view_customers',
            'manage_customers',
        ],
    ],
];
