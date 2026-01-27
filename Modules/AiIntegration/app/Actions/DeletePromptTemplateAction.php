<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\Models\AiPromptTemplate;

/**
 * Action to delete an AI prompt template
 */
class DeletePromptTemplateAction
{
    /**
     * @throws \Exception
     */
    public function execute(AiPromptTemplate $promptTemplate): bool
    {
        if (!$promptTemplate->canBeDeleted()) {
            throw new \Exception('System templates cannot be deleted.');
        }

        return $promptTemplate->delete();
    }
}
