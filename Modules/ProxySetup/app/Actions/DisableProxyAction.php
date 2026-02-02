<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Actions;

use Modules\ProxySetup\Models\Proxy;

class DisableProxyAction
{
    public function execute(Proxy $proxy): Proxy
    {
        $proxy->update(['is_enabled' => false]);

        return $proxy->fresh();
    }
}
