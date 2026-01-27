<?php

declare(strict_types=1);

namespace Modules\Authentication\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authentication\Models\AuthenticationSetting;

class AuthenticationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = $this->getDefaultSettings();

        foreach ($defaultSettings as $key => $value) {
            $type = $this->getValueType($value);
            
            AuthenticationSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type' => $type,
                ]
            );
        }

        $this->command->info('Authentication settings seeded successfully.');
    }

    /**
     * Get default settings structure
     */
    private function getDefaultSettings(): array
    {
        return [
            // Features - Email Verification
            'features.email_verification.enabled' => true,
            'features.email_verification.required' => true,

            // Features - Password Reset
            'features.password_reset.enabled' => true,
            'features.password_reset.token_expiry' => 60,
            'features.password_reset.rate_limit' => 3,

            // Features - Magic Link
            'features.magic_link.enabled' => true,
            'features.magic_link.token_expiry' => 15,
            'features.magic_link.rate_limit' => 3,

            // Features - OTP 2FA
            'features.otp_2fa.enabled' => true,
            'features.otp_2fa.required_for_admin' => false,
            'features.otp_2fa.required_for_customer' => true,

            // Features - Social Auth
            'features.social_auth.enabled' => false,
            'features.social_auth.providers' => ['google', 'facebook', 'tiktok'], // Array type
            'features.social_auth.provider_configs.google.client_id' => null,
            'features.social_auth.provider_configs.google.client_secret' => null,
            'features.social_auth.provider_configs.facebook.client_id' => null,
            'features.social_auth.provider_configs.facebook.client_secret' => null,
            'features.social_auth.provider_configs.tiktok.client_id' => null,
            'features.social_auth.provider_configs.tiktok.client_secret' => null,
            'features.social_auth.provider_configs.tiktok.mode' => 'sandbox', // Default to sandbox for testing

            // Endpoints - Public
            'endpoints.public.login.enabled' => true,
            'endpoints.public.register.enabled' => true,
            'endpoints.public.password_reset.enabled' => true,
            'endpoints.public.email_verification.enabled' => true,
            'endpoints.public.magic_link.enabled' => true,
            'endpoints.public.otp.enabled' => true,
            'endpoints.public.social_auth.enabled' => true,

            // Endpoints - Customer
            'endpoints.customer.logout.enabled' => true,
            'endpoints.customer.refresh_token.enabled' => true,

            // Password Requirements
            'password.min_length' => 8,
            'password.require_uppercase' => true,
            'password.require_number' => true,
            'password.require_special' => true,

            // Token Settings
            'token.sanctum_expiry' => 1440, // 24 hours in minutes
            'token.jwt_expiry' => 60, // 1 hour in minutes
            'token.refresh_expiry' => 43200, // 30 days in minutes
        ];
    }

    /**
     * Get value type
     */
    private function getValueType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_array($value)) {
            return 'array';
        }
        if (is_string($value) && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
            // Check if it's a JSON string
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return 'array';
            }
        }
        return 'string';
    }
}
