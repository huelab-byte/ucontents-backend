<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Actions;

use Modules\ProxySetup\Models\Proxy;

class EnableProxyAction
{
    public function execute(Proxy $proxy): Proxy
    {
        $proxy->update(['is_enabled' => true]);

        return $proxy->fresh();
    }
}
