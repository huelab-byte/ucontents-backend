<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Services\Providers\Adapters;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\SocialConnection\Services\Providers\ProviderAdapterInterface;
use Modules\UserManagement\Models\User;

class GoogleAdapter implements ProviderAdapterInterface
{
    public function provider(): string
    {
        return 'google';
    }

    public function makeAuthorizationRedirect(User $user, SocialProviderApp $app, string $callbackUrl, string $state): RedirectResponse
    {
        config([
            'services.google' => [
                'client_id' => $app->client_id,
                'client_secret' => $app->client_secret,
                'redirect' => $callbackUrl,
            ]
        ]);

        $scopes = $app->scopes ?: [
            'openid',
            'profile',
            'email',
            'https://www.googleapis.com/auth/youtube', // Use the verified "Manage your YouTube account" scope to avoid unverified warning
        ];

        return Socialite::driver('google')
            ->redirectUrl($callbackUrl)
            ->stateless()
            ->scopes($scopes)
            ->with(['state' => $state, 'access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function handleCallback(User $user, SocialProviderApp $app, string $callbackUrl, ?array $channelTypes = null): array
    {
        config([
            'services.google' => [
                'client_id' => $app->client_id,
                'client_secret' => $app->client_secret,
                'redirect' => $callbackUrl,
            ]
        ]);

        $socialiteUser = Socialite::driver('google')
            ->redirectUrl($callbackUrl)
            ->stateless()
            ->user();

        $accessToken = $socialiteUser->token;
        $raw = $socialiteUser->getRaw() ?? [];

        // YouTube channels for the authenticated user
        $channels = [];
        try {
            $ytResp = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet',
                    'mine' => 'true',
                    'maxResults' => 50,
                ]);

            if ($ytResp->successful()) {
                $items = $ytResp->json('items', []) ?? [];
                foreach ($items as $item) {
                    $snippet = $item['snippet'] ?? [];
                    $channels[] = [
                        'provider' => 'google',
                        'type' => 'youtube_channel',
                        'provider_channel_id' => (string) ($item['id'] ?? ''),
                        'name' => (string) ($snippet['title'] ?? 'YouTube Channel'),
                        'username' => null,
                        'avatar_url' => $snippet['thumbnails']['default']['url'] ?? null,
                        'metadata' => [
                            'channel_id' => $item['id'] ?? null,
                        ],
                        'token_context' => null,
                    ];
                }
            } else {
                Log::warning('GoogleAdapter: failed to fetch YouTube channels', [
                    'status' => $ytResp->status(),
                    'body' => $ytResp->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('GoogleAdapter: exception fetching YouTube channels', ['message' => $e->getMessage()]);
        }

        return [
            'identity' => [
                'provider_account_id' => (string) $socialiteUser->getId(),
                'email' => $socialiteUser->getEmail(),
                'display_name' => $socialiteUser->getName(),
                'avatar_url' => $socialiteUser->getAvatar(),
                'raw' => $raw,
            ],
            'tokens' => [
                'access_token' => $accessToken,
                'refresh_token' => $socialiteUser->refreshToken ?? null,
                'expires_at' => null,
                'scopes' => $app->scopes ?? [],
            ],
            'channels' => $channels,
        ];
    }
}

