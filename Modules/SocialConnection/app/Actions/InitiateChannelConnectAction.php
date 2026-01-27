<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Illuminate\Support\Facades\Cache;
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

        Cache::put($this->stateCacheKey($provider, $state), [
            'user_id' => $user->id,
            'channel_types' => $channelTypes, // Filter for Facebook-only or Instagram-only
        ], now()->addMinutes(10));

        $adapter = $this->factory->make($provider);

        $redirect = $adapter->makeAuthorizationRedirect($user, $app, $callbackUrl, $state);

        return method_exists($redirect, 'getTargetUrl') ? $redirect->getTargetUrl() : (string) $redirect;
    }

    private function stateCacheKey(string $provider, string $state): string
    {
        return "social_connection:oauth_state:{$provider}:{$state}";
    }
}

