<?php

declare(strict_types=1);

return [
    'name' => 'Support',
    'enabled' => true,

    'features' => [
        'tickets' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],

    'endpoints' => [
        'admin' => [
            'tickets' => ['enabled' => true],
        ],
        'customer' => [
            'tickets' => ['enabled' => true],
        ],
    ],

    'permissions' => [
        'admin' => [
            'view_all_tickets',
            'manage_tickets',
            'assign_tickets',
        ],
        'customer' => [
            'view_own_tickets',
            'create_tickets',
            'reply_to_own_tickets',
        ],
    ],
];
