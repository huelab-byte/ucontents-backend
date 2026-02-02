<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | BulkPosting Module Configuration
    |--------------------------------------------------------------------------
    |
    | This is a customer-only module. Bulk posting campaigns are created and
    | managed by individual customers for scheduling their social media content.
    | Admins can view customer campaigns through the CustomerManagement module
    | but do not have separate admin endpoints here.
    |
    */
    
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
