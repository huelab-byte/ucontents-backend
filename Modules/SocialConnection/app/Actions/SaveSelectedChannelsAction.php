<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\SocialConnection\Models\SocialConnectionAccount;
use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\UserManagement\Models\User;

class SaveSelectedChannelsAction
{
    public function execute(User $user, string $provider, string $selectionToken, array $selectedChannelIds): array
    {
        $cacheKey = $this->selectionCacheKey($provider, $user->id, $selectionToken);
        $cached = Cache::pull($cacheKey);

        if (!$cached) {
            throw new \RuntimeException('Selection session expired. Please try connecting again.');
        }

        if ($cached['user_id'] !== $user->id || $cached['provider'] !== $provider) {
            throw new \RuntimeException('Invalid selection session.');
        }

        $identity = $cached['identity'] ?? [];
        $tokens = $cached['tokens'] ?? [];
        $allChannels = $cached['channels'] ?? [];

        // Filter to only selected channels
        $selectedChannels = [];
        foreach ($allChannels as $ch) {
            $channelKey = $this->getChannelKey($ch);
            if (in_array($channelKey, $selectedChannelIds, true)) {
                $selectedChannels[] = $ch;
            }
        }

        if (empty($selectedChannels)) {
            throw new \RuntimeException('Please select at least one channel.');
        }

        return DB::transaction(function () use ($user, $provider, $identity, $tokens, $selectedChannels) {
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

            foreach ($selectedChannels as $ch) {
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

    public function getAvailableChannels(User $user, string $provider, string $selectionToken): array
    {
        $cacheKey = $this->selectionCacheKey($provider, $user->id, $selectionToken);
        $cached = Cache::get($cacheKey);

        if (!$cached) {
            throw new \RuntimeException('Selection session expired. Please try connecting again.');
        }

        if ($cached['user_id'] !== $user->id || $cached['provider'] !== $provider) {
            throw new \RuntimeException('Invalid selection session.');
        }

        $channels = $cached['channels'] ?? [];
        
        // Return channels with a unique key for frontend selection
        return array_map(function ($ch) {
            return [
                'key' => $this->getChannelKey($ch),
                'provider' => $ch['provider'] ?? 'meta',
                'type' => $ch['type'] ?? '',
                'provider_channel_id' => $ch['provider_channel_id'] ?? '',
                'name' => $ch['name'] ?? 'Channel',
                'username' => $ch['username'] ?? null,
                'avatar_url' => $ch['avatar_url'] ?? null,
                'metadata' => $ch['metadata'] ?? [],
            ];
        }, $channels);
    }

    private function getChannelKey(array $channel): string
    {
        $provider = $channel['provider'] ?? '';
        $type = $channel['type'] ?? '';
        $id = $channel['provider_channel_id'] ?? '';
        return "{$provider}:{$type}:{$id}";
    }

    private function selectionCacheKey(string $provider, int $userId, string $token): string
    {
        return "social_connection:selection:{$provider}:{$userId}:{$token}";
    }
}
