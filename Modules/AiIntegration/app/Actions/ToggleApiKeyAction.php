<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\Models\AiApiKey;
use Modules\AiIntegration\Services\AiApiKeyService;

/**
 * Action to enable/disable an AI API key
 */
class ToggleApiKeyAction
{
    public function __construct(
        private AiApiKeyService $apiKeyService
    ) {
    }

    public function execute(AiApiKey $apiKey, bool $isActive): AiApiKey
    {
        return $this->apiKeyService->toggleApiKey($apiKey, $isActive);
    }
}
