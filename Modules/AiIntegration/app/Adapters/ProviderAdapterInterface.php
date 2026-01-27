<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Adapters;

use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\AiIntegration\Models\AiApiKey;

/**
 * Interface for AI provider adapters
 */
interface ProviderAdapterInterface
{
    /**
     * Call the AI model using the provider's SDK
     *
     * @param AiApiKey $apiKey The API key to use
     * @param AiModelCallDTO $dto The request DTO
     * @return array Response array with: content, prompt_tokens, completion_tokens, total_tokens, model
     * @throws \Exception
     */
    public function callModel(AiApiKey $apiKey, AiModelCallDTO $dto): array;
}
