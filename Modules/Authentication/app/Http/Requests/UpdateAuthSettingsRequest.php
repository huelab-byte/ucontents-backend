<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'features' => 'nullable|array',
            'features.magic_link' => 'nullable|array',
            'features.magic_link.enabled' => 'nullable|boolean',
            'features.magic_link.token_expiry' => 'nullable|integer|min:1|max:1440',
            'features.magic_link.rate_limit' => 'nullable|integer|min:1|max:100',
            'features.otp_2fa' => 'nullable|array',
            'features.otp_2fa.enabled' => 'nullable|boolean',
            'features.otp_2fa.required_for_admin' => 'nullable|boolean',
            'features.otp_2fa.required_for_customer' => 'nullable|boolean',
            'features.email_verification' => 'nullable|array',
            'features.email_verification.enabled' => 'nullable|boolean',
            'features.email_verification.required' => 'nullable|boolean',
            'features.password_reset' => 'nullable|array',
            'features.password_reset.enabled' => 'nullable|boolean',
            'features.password_reset.token_expiry' => 'nullable|integer|min:1|max:1440',
            'features.password_reset.rate_limit' => 'nullable|integer|min:1|max:100',
            'features.social_auth' => 'nullable|array',
            'features.social_auth.enabled' => 'nullable|boolean',
            'features.social_auth.providers' => 'nullable|array',
            'features.social_auth.provider_configs' => 'nullable|array',
            'features.social_auth.provider_configs.google' => 'nullable|array',
            'features.social_auth.provider_configs.google.client_id' => 'nullable|string',
            'features.social_auth.provider_configs.google.client_secret' => 'nullable|string',
            'features.social_auth.provider_configs.facebook' => 'nullable|array',
            'features.social_auth.provider_configs.facebook.client_id' => 'nullable|string',
            'features.social_auth.provider_configs.facebook.client_secret' => 'nullable|string',
            'features.social_auth.provider_configs.tiktok' => 'nullable|array',
            'features.social_auth.provider_configs.tiktok.client_id' => 'nullable|string',
            'features.social_auth.provider_configs.tiktok.client_secret' => 'nullable|string',
            'features.social_auth.provider_configs.tiktok.mode' => 'nullable|string|in:sandbox,live',
            'endpoints' => 'nullable|array',
            'endpoints.public' => 'nullable|array',
            'endpoints.public.login' => 'nullable|array',
            'endpoints.public.login.enabled' => 'nullable|boolean',
            'endpoints.public.register' => 'nullable|array',
            'endpoints.public.register.enabled' => 'nullable|boolean',
            'endpoints.public.magic_link' => 'nullable|array',
            'endpoints.public.magic_link.enabled' => 'nullable|boolean',
            'endpoints.public.otp' => 'nullable|array',
            'endpoints.public.otp.enabled' => 'nullable|boolean',
            'endpoints.public.password_reset' => 'nullable|array',
            'endpoints.public.password_reset.enabled' => 'nullable|boolean',
            'endpoints.public.social_auth' => 'nullable|array',
            'endpoints.public.social_auth.enabled' => 'nullable|boolean',
            'password' => 'nullable|array',
            'password.min_length' => 'nullable|integer|min:6|max:128',
            'password.require_uppercase' => 'nullable|boolean',
            'password.require_number' => 'nullable|boolean',
            'password.require_special' => 'nullable|boolean',
            'token' => 'nullable|array',
            'token.sanctum_expiry' => 'nullable|integer|min:1|max:10080',
            'token.jwt_expiry' => 'nullable|integer|min:1|max:1440',
            'token.refresh_expiry' => 'nullable|integer|min:1|max:43200',
            'rate_limits' => 'nullable|array',
            'rate_limits.admin' => 'nullable|array',
            'rate_limits.admin.limit' => 'nullable|integer|min:1|max:10000',
            'rate_limits.admin.period' => 'nullable|integer|min:1|max:3600',
            'rate_limits.customer' => 'nullable|array',
            'rate_limits.customer.limit' => 'nullable|integer|min:1|max:10000',
            'rate_limits.customer.period' => 'nullable|integer|min:1|max:3600',
            'rate_limits.public' => 'nullable|array',
            'rate_limits.public.limit' => 'nullable|integer|min:1|max:10000',
            'rate_limits.public.period' => 'nullable|integer|min:1|max:3600',
            'rate_limits.guest' => 'nullable|array',
            'rate_limits.guest.limit' => 'nullable|integer|min:1|max:10000',
            'rate_limits.guest.period' => 'nullable|integer|min:1|max:3600',
        ];
    }
}
