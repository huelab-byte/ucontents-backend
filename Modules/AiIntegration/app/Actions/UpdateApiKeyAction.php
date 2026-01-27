<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\DTOs\UpdateApiKeyDTO;
use Modules\AiIntegration\Models\AiApiKey;
use Modules\AiIntegration\Services\AiApiKeyService;

/**
 * Action to update an AI API key
 */
class UpdateApiKeyAction
{
    public function __construct(
        private AiApiKeyService $apiKeyService
    ) {
    }

    public function execute(AiApiKey $apiKey, UpdateApiKeyDTO $dto): AiApiKey
    {
        return $this->apiKeyService->updateApiKey($apiKey, $dto);
    }
}
