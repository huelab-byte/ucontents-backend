<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PaymentGateway\Models\PaymentGateway;
use Modules\PaymentGateway\Models\InvoiceTemplate;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Stripe gateway (inactive by default)
        PaymentGateway::firstOrCreate(
            ['name' => 'stripe'],
            [
                'display_name' => 'Stripe',
                'is_active' => false,
                'is_test_mode' => true,
                'credentials' => [],
                'settings' => [],
                'description' => 'Stripe payment gateway integration',
            ]
        );

        // Seed PayPal gateway (inactive by default)
        PaymentGateway::firstOrCreate(
            ['name' => 'paypal'],
            [
                'display_name' => 'PayPal',
                'is_active' => false,
                'is_test_mode' => true,
                'credentials' => [],
                'settings' => [],
                'description' => 'PayPal payment gateway integration',
            ]
        );

        // Seed a default invoice template (active + default)
        $template = InvoiceTemplate::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Invoice Template',
                'description' => 'System default invoice template',
                'header_html' => '<div style="text-align:left;"><h1>Invoice</h1><p>{{ company_name }}</p></div>',
                'footer_html' => '<div style="font-size:12px;text-align:center;color:#6b7280;">Thank you for your business.</div>',
                'settings' => [
                    'primary_color' => '#2563eb',
                    'accent_color' => '#111827',
                    'show_company_logo' => true,
                ],
                'is_active' => true,
                'is_default' => true,
                'created_by' => null,
            ]
        );

        // Ensure this seeded template is the single default
        $template->setAsDefault();
    }
}
