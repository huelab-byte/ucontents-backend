<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Services;

use Modules\ProxySetup\Models\Proxy;
use Modules\ProxySetup\Models\ProxySetting;
use Modules\SocialConnection\Models\SocialConnectionChannel;

/**
 * Service for retrieving proxy configuration for automation tasks
 */
class ProxyService
{
    /**
     * Get a proxy for a specific channel based on user settings
     *
     * @param SocialConnectionChannel $channel
     * @return Proxy|null
     */
    public function getProxyForChannel(SocialConnectionChannel $channel): ?Proxy
    {
        $settings = ProxySetting::getOrCreateForUser($channel->user_id);

        if ($settings->apply_to_all_channels) {
            // Get all enabled proxies for user
            $proxies = Proxy::where('user_id', $channel->user_id)
                ->where('is_enabled', true)
                ->get();
        } else {
            // Get proxies assigned to this specific channel
            $proxies = $channel->proxies()
                ->where('is_enabled', true)
                ->get();
        }

        if ($proxies->isEmpty()) {
            return null;
        }

        return $settings->use_random_proxy
            ? $proxies->random()
            : $proxies->first();
    }

    /**
     * Check if automation should stop on proxy failure
     *
     * @param int $userId
     * @return bool
     */
    public function shouldStopOnFailure(int $userId): bool
    {
        $settings = ProxySetting::where('user_id', $userId)->first();

        return $settings?->on_proxy_failure === 'stop_automation';
    }

    /**
     * Get proxy configuration for cURL usage
     *
     * @param Proxy $proxy
     * @return array{proxy: string, proxy_type: int, proxy_auth: string|null}
     */
    public function getProxyCurlConfig(Proxy $proxy): array
    {
        $proxyType = match ($proxy->type) {
            'socks4' => CURLPROXY_SOCKS4,
            'socks5' => CURLPROXY_SOCKS5,
            'https' => CURLPROXY_HTTPS,
            default => CURLPROXY_HTTP,
        };

        $proxyAuth = null;
        if ($proxy->username && $proxy->password) {
            $proxyAuth = "{$proxy->username}:{$proxy->password}";
        }

        return [
            'proxy' => "{$proxy->host}:{$proxy->port}",
            'proxy_type' => $proxyType,
            'proxy_auth' => $proxyAuth,
        ];
    }

    /**
     * Get all enabled proxies for a user
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection<Proxy>
     */
    public function getEnabledProxiesForUser(int $userId)
    {
        return Proxy::where('user_id', $userId)
            ->where('is_enabled', true)
            ->get();
    }
}
