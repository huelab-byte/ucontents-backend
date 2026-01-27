<?php

declare(strict_types=1);

return [
    'name' => 'EmailManagement',
    'enabled' => true,
    
    'features' => [
        'email_queue' => [
            'enabled' => true,
            'queue_name' => 'emails',
        ],
        'smtp_configuration' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'email_templates' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'email_notifications' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],
    
    'endpoints' => [
        'admin' => [
            'smtp_config' => ['enabled' => true],
            'email_templates' => ['enabled' => true],
            'send_test_email' => ['enabled' => true],
        ],
        'customer' => [
            'send_email' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => ['manage_smtp', 'manage_templates', 'send_emails'],
        'customer' => ['send_emails'],
    ],
    
    'default_queue' => env('EMAIL_QUEUE', null), // null = use default queue, 'emails' = use emails queue
    'max_retries' => 3,
    'retry_delay' => 60, // seconds
];
