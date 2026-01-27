<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Services\Providers;

use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\UserManagement\Models\User;

interface ProviderAdapterInterface
{
    /**
     * Return the OAuth provider name (meta/google/tiktok).
     */
    public function provider(): string;

    /**
     * Build an authorization redirect URL to send the user to.
     */
    public function makeAuthorizationRedirect(User $user, SocialProviderApp $app, string $callbackUrl, string $state);

    /**
     * Handle callback: exchange code and return a normalized payload:
     * - identity: provider_account_id, email, display_name, avatar_url, raw
     * - tokens: access_token, refresh_token, expires_at, scopes
     * - channels: normalized list of channels to upsert
     * 
     * @param array|null $channelTypes Optional filter: ['facebook_page', 'facebook_profile', 'instagram_business']
     */
    public function handleCallback(User $user, SocialProviderApp $app, string $callbackUrl, ?array $channelTypes = null): array;
}

