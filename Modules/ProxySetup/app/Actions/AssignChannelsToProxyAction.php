<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Actions;

use Modules\ProxySetup\Models\Proxy;
use Modules\SocialConnection\Models\SocialConnectionChannel;

class AssignChannelsToProxyAction
{
    /**
     * Assign channels to a proxy (replaces existing assignments)
     *
     * @param Proxy $proxy
     * @param array<int> $channelIds
     * @return Proxy
     */
    public function execute(Proxy $proxy, array $channelIds): Proxy
    {
        // Validate that all channels belong to the same user as the proxy
        $validChannelIds = SocialConnectionChannel::query()
            ->where('user_id', $proxy->user_id)
            ->whereIn('id', $channelIds)
            ->pluck('id')
            ->toArray();

        // Sync the channels (replaces all existing assignments)
        $proxy->channels()->sync($validChannelIds);

        return $proxy->fresh()->load('channels');
    }
}
