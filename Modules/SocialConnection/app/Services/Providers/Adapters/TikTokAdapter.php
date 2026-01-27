<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Services\Providers\Adapters;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\SocialConnection\Services\Providers\ProviderAdapterInterface;
use Modules\UserManagement\Models\User;

class TikTokAdapter implements ProviderAdapterInterface
{
    public function provider(): string
    {
        return 'tiktok';
    }

    public function makeAuthorizationRedirect(User $user, SocialProviderApp $app, string $callbackUrl, string $state): RedirectResponse
    {
        config(['services.tiktok' => [
            'client_id' => $app->client_id,
            'client_secret' => $app->client_secret,
            'redirect' => $callbackUrl,
        ]]);

        // Scopes depend on TikTok app type; keep configurable.
        $scopes = $app->scopes ?: ['user.info.basic'];

        return Socialite::driver('tiktok')
            ->redirectUrl($callbackUrl)
            ->stateless()
            ->scopes($scopes)
            ->with(['state' => $state])
            ->redirect();
    }

    public function handleCallback(User $user, SocialProviderApp $app, string $callbackUrl, ?array $channelTypes = null): array
    {
        config(['services.tiktok' => [
            'client_id' => $app->client_id,
            'client_secret' => $app->client_secret,
            'redirect' => $callbackUrl,
        ]]);

        $socialiteUser = Socialite::driver('tiktok')
            ->redirectUrl($callbackUrl)
            ->stateless()
            ->user();

        $raw = $socialiteUser->getRaw() ?? [];

        // TikTok: treat the profile as a single channel.
        $channels = [[
            'provider' => 'tiktok',
            'type' => 'tiktok_profile',
            'provider_channel_id' => (string) $socialiteUser->getId(),
            'name' => (string) ($socialiteUser->getName() ?? 'TikTok'),
            'username' => $raw['username'] ?? null,
            'avatar_url' => $socialiteUser->getAvatar(),
            'metadata' => [
                'tiktok_user_id' => $socialiteUser->getId(),
            ],
            'token_context' => null,
        ]];

        return [
            'identity' => [
                'provider_account_id' => (string) $socialiteUser->getId(),
                'email' => $socialiteUser->getEmail(),
                'display_name' => $socialiteUser->getName(),
                'avatar_url' => $socialiteUser->getAvatar(),
                'raw' => $raw,
            ],
            'tokens' => [
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken ?? null,
                'expires_at' => null,
                'scopes' => $app->scopes ?? [],
            ],
            'channels' => $channels,
        ];
    }
}

