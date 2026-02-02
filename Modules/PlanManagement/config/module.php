<?php

declare(strict_types=1);

return [
    'name' => 'PlanManagement',
    'enabled' => true,

    'features' => [
        'plans' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],

    'endpoints' => [
        'admin' => [
            'plans' => ['enabled' => true],
        ],
        'customer' => [
            'plans' => ['enabled' => true],
        ],
        'public' => [
            'plans' => ['enabled' => true],
        ],
    ],

    'permissions' => [
        'admin' => [
            'view_plans',
            'manage_plans',
        ],
        'customer' => [
            'view_own_subscription',
            'subscribe_to_plan',
        ],
    ],

    'subscription_expiring_days' => (int) (env('PLAN_SUBSCRIPTION_EXPIRING_DAYS', 7)),
];
