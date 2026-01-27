<?php

declare(strict_types=1);

return [
    'name' => 'NotificationManagement',
    'enabled' => true,

    'features' => [
        'notifications' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'announcements' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'realtime' => [
            'enabled' => true,
            'driver' => 'pusher',
        ],
    ],

    'endpoints' => [
        'admin' => [
            'announcements' => ['enabled' => true],
            'pusher_auth' => ['enabled' => true],
        ],
        'customer' => [
            'notifications' => ['enabled' => true],
            'pusher_auth' => ['enabled' => true],
        ],
    ],

    'permissions' => [
        'admin' => [
            'manage_announcements',
            'view_admin_notifications',
        ],
        'customer' => [
            'view_notifications',
        ],
    ],
];

