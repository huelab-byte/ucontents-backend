<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Actions;

use Modules\ProxySetup\Models\Proxy;
use Modules\ProxySetup\Services\ProxyTestService;

class TestProxyConnectionAction
{
    public function __construct(
        private readonly ProxyTestService $testService
    ) {}

    public function execute(Proxy $proxy): array
    {
        $result = $this->testService->test($proxy);

        // Update proxy with test results
        $proxy->update([
            'last_checked_at' => now(),
            'last_check_status' => $result['success'] ? 'success' : 'failed',
            'last_check_message' => $result['message'],
        ]);

        return $result;
    }
}
