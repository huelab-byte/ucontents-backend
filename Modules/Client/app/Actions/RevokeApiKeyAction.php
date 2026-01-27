<?php

declare(strict_types=1);

namespace Modules\Client\Actions;

use Modules\Client\Models\ApiKey;
use Modules\Client\Services\ApiKeyService;

/**
 * Action to revoke an API key
 */
class RevokeApiKeyAction
{
    public function __construct(
        private ApiKeyService $apiKeyService
    ) {
    }

    public function execute(ApiKey $apiKey, ?string $reason = null): void
    {
        $this->apiKeyService->revokeApiKey($apiKey, $reason);
    }
}
