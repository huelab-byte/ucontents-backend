<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\AiIntegration\Services\AiModelCallService;

/**
 * Action to call an AI model
 */
class CallAiModelAction
{
    public function __construct(
        private AiModelCallService $modelCallService
    ) {
    }

    public function execute(AiModelCallDTO $dto, ?int $userId = null): array
    {
        return $this->modelCallService->callModel($dto, $userId);
    }
}
