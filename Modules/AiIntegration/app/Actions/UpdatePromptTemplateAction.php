<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\DTOs\UpdatePromptTemplateDTO;
use Modules\AiIntegration\Models\AiPromptTemplate;

/**
 * Action to update a prompt template
 */
class UpdatePromptTemplateAction
{
    public function execute(AiPromptTemplate $template, UpdatePromptTemplateDTO $dto): AiPromptTemplate
    {
        $updateData = array_filter([
            'name' => $dto->name,
            'template' => $dto->template,
            'description' => $dto->description,
            'variables' => $dto->variables,
            'category' => $dto->category,
            'provider_slug' => $dto->providerSlug,
            'model' => $dto->model,
            'settings' => $dto->settings,
            'is_active' => $dto->isActive,
        ], fn($value) => $value !== null);

        $template->update($updateData);

        return $template->fresh();
    }
}
