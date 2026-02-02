<?php

declare(strict_types=1);

return [
    'name' => 'BulkPosting',
    'enabled' => true,

    'features' => [
        'bulk_posting' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],

    'endpoints' => [
        'customer' => [
            'manage_campaigns' => ['enabled' => true],
        ],
    ],

    'permissions' => [
        'customer' => [
            'view_bulk_posting_campaigns',
            'manage_bulk_posting_campaigns',
        ],
    ],

    'schedule_conditions' => ['minute', 'hourly', 'daily', 'weekly', 'monthly'],
];
