<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\Models\AiApiKey;

/**
 * Action to delete an AI API key
 */
class DeleteApiKeyAction
{
    public function execute(AiApiKey $apiKey): bool
    {
        return $apiKey->delete();
    }
}
