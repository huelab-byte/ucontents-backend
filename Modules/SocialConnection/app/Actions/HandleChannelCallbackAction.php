<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SocialConnection\Models\SocialConnectionAccount;
use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\SocialConnection\Services\Providers\ProviderAdapterFactory;
use Modules\UserManagement\Models\User;

class HandleChannelCallbackAction
{
    public function __construct(
        private readonly ProviderAdapterFactory $factory
    ) {
    }

    public function execute(string $provider, SocialProviderApp $app, string $callbackUrl, ?string $stateFromRequest): array
    {
        if (!$stateFromRequest) {
            throw new \RuntimeException('Invalid OAuth state. Please try connecting again.');
        }

        // One-time use: resolve initiating user from state (database first so it works with any cache driver)
        $statePayload = $this->pullStatePayload($provider, $stateFromRequest);
        $userId = $statePayload['user_id'] ?? null;
        $channelTypes = $statePayload['channel_types'] ?? null;
        if (!$userId) {
            throw new \RuntimeException('OAuth session expired. Please try connecting again.');
        }

        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        $adapter = $this->factory->make($provider);
        $payload = $adapter->handleCallback($user, $app, $callbackUrl, $channelTypes);

        // For Meta provider, store channels temporarily in cache for user selection
        // Other providers can save immediately
        if ($provider === 'meta') {
            $selectionToken = \Illuminate\Support\Str::random(32);
            $cacheKey = $this->selectionCacheKey($provider, $user->id, $selectionToken);
            
            // Store account data and channels temporarily (15 minutes expiry)
            Cache::put($cacheKey, [
                'user_id' => $user->id,
                'provider' => $provider,
                'identity' => $payload['identity'] ?? [],
                'tokens' => $payload['tokens'] ?? [],
                'channels' => $payload['channels'] ?? [],
            ], now()->addMinutes(15));

            return [
                'selection_token' => $selectionToken,
                'channels_available' => count($payload['channels'] ?? []),
            ];
        }

        // For non-Meta providers, save immediately (existing behavior)
        return DB::transaction(function () use ($user, $provider, $payload) {
            $identity = $payload['identity'] ?? [];
            $tokens = $payload['tokens'] ?? [];
            $channels = $payload['channels'] ?? [];

            $account = SocialConnectionAccount::updateOrCreate(
                [
                    'provider' => $provider,
                    'provider_account_id' => (string) ($identity['provider_account_id'] ?? ''),
                ],
                [
                    'user_id' => $user->id,
                    'email' => $identity['email'] ?? null,
                    'display_name' => $identity['display_name'] ?? null,
                    'avatar_url' => $identity['avatar_url'] ?? null,
                    'access_token' => $tokens['access_token'] ?? null,
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                    'expires_at' => $tokens['expires_at'] ?? null,
                    'scopes' => $tokens['scopes'] ?? [],
                    'raw' => $identity['raw'] ?? [],
                ]
            );

            $upserted = 0;

            foreach ($channels as $ch) {
                $providerChannelId = (string) ($ch['provider_channel_id'] ?? '');
                $type = (string) ($ch['type'] ?? '');
                if ($providerChannelId === '' || $type === '') {
                    continue;
                }

                SocialConnectionChannel::updateOrCreate(
                    [
                        'provider' => $provider,
                        'type' => $type,
                        'provider_channel_id' => $providerChannelId,
                    ],
                    [
                        'user_id' => $user->id,
                        'social_connection_account_id' => $account->id,
                        'name' => (string) ($ch['name'] ?? 'Channel'),
                        'username' => $ch['username'] ?? null,
                        'avatar_url' => $ch['avatar_url'] ?? null,
                        'is_active' => true,
                        'metadata' => $ch['metadata'] ?? [],
                        'token_context' => $ch['token_context'] ?? null,
                    ]
                );

                $upserted++;
            }

            return [
                'account' => $account,
                'channels_upserted' => $upserted,
            ];
        });
    }

    /**
     * Resolve and consume state payload (one-time use). Tries database first so it works with array cache locally.
     */
    private function pullStatePayload(string $provider, string $state): ?array
    {
        if (Schema::hasTable('social_connection_oauth_states')) {
            $row = DB::table('social_connection_oauth_states')
                ->where('provider', $provider)
                ->where('state', $state)
                ->where('expires_at', '>', now())
                ->first();
            if ($row) {
                DB::table('social_connection_oauth_states')
                    ->where('id', $row->id)
                    ->delete();
                $payload = json_decode($row->payload, true);
                return is_array($payload) ? $payload : null;
            }
        }

        $payload = Cache::pull($this->stateCacheKey($provider, $state));

        return is_array($payload) ? $payload : null;
    }

    private function stateCacheKey(string $provider, string $state): string
    {
        return "social_connection:oauth_state:{$provider}:{$state}";
    }

    private function selectionCacheKey(string $provider, int $userId, string $token): string
    {
        return "social_connection:selection:{$provider}:{$userId}:{$token}";
    }
}

