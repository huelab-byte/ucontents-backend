<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Actions;

use Modules\ProxySetup\Models\Proxy;

class DeleteProxyAction
{
    public function execute(Proxy $proxy): bool
    {
        // Remove all channel assignments first
        $proxy->channelAssignments()->delete();

        // Soft delete the proxy
        return $proxy->delete();
    }
}
