<?php

declare(strict_types=1);

return [
    'name' => 'PaymentGateway',
    'enabled' => true,

    'features' => [
        'gateway_configuration' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'payment_processing' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'invoice_management' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'subscription_management' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'refund_processing' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],

    'endpoints' => [
        'admin' => [
            'gateways' => [
                'enabled' => true,
            ],
            'invoices' => [
                'enabled' => true,
            ],
            'payments' => [
                'enabled' => true,
            ],
            'subscriptions' => [
                'enabled' => true,
            ],
            'refunds' => [
                'enabled' => true,
            ],
        ],
        'customer' => [
            'invoices' => [
                'enabled' => true,
            ],
            'payments' => [
                'enabled' => true,
            ],
            'subscriptions' => [
                'enabled' => true,
            ],
        ],
    ],

    'permissions' => [
        'admin' => [
            'manage_payment_gateways',
            'view_all_invoices',
            'edit_invoices',
            'process_refunds',
            'view_all_payments',
            'view_all_subscriptions',
        ],
        'customer' => [
            'view_own_invoices',
            'make_payments',
            'manage_own_subscriptions',
            'request_refunds',
        ],
    ],

    'supported_gateways' => [
        'stripe' => [
            'name' => 'Stripe',
            'enabled' => true,
        ],
        'paypal' => [
            'name' => 'PayPal',
            'enabled' => true,
        ],
    ],

    'subscription_intervals' => [
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
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
    ],
];
