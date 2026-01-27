<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\DTOs\CreateApiKeyDTO;
use Modules\AiIntegration\Models\AiApiKey;
use Modules\AiIntegration\Services\AiApiKeyService;

/**
 * Action to create a new AI API key
 */
class CreateApiKeyAction
{
    public function __construct(
        private AiApiKeyService $apiKeyService
    ) {
    }

    public function execute(CreateApiKeyDTO $dto): AiApiKey
    {
        return $this->apiKeyService->createApiKey($dto);
    }
}
