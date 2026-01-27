<?php

declare(strict_types=1);

return [
    'stripe' => [
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],
];
