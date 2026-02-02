<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\SocialConnection\Services\Providers\ProviderAdapterFactory;
use Modules\UserManagement\Models\User;

class InitiateChannelConnectAction
{
    public function __construct(
        private readonly ProviderAdapterFactory $factory
    ) {
    }

    public function execute(User $user, string $provider, SocialProviderApp $app, string $callbackUrl, ?array $channelTypes = null): string
    {
        $state = bin2hex(random_bytes(16));
        $payload = [
            'user_id' => $user->id,
            'channel_types' => $channelTypes,
        ];
        $expiresAt = now()->addMinutes(10);

        // Store in database so state persists across requests (works with any cache driver, including array)
        if (Schema::hasTable('social_connection_oauth_states')) {
            DB::table('social_connection_oauth_states')->insert([
                'provider' => $provider,
                'state' => $state,
                'payload' => json_encode($payload),
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::put($this->stateCacheKey($provider, $state), $payload, $expiresAt);

        $adapter = $this->factory->make($provider);

        $redirect = $adapter->makeAuthorizationRedirect($user, $app, $callbackUrl, $state);

        return method_exists($redirect, 'getTargetUrl') ? $redirect->getTargetUrl() : (string) $redirect;
    }

    private function stateCacheKey(string $provider, string $state): string
    {
        return "social_connection:oauth_state:{$provider}:{$state}";
    }
}

