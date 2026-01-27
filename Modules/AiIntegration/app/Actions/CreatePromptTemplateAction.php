<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\DTOs\CreatePromptTemplateDTO;
use Modules\AiIntegration\Models\AiPromptTemplate;

/**
 * Action to create a prompt template
 */
class CreatePromptTemplateAction
{
    public function execute(CreatePromptTemplateDTO $dto): AiPromptTemplate
    {
        return AiPromptTemplate::create([
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
            'template' => $dto->template,
            'variables' => $dto->variables,
            'category' => $dto->category,
            'provider_slug' => $dto->providerSlug,
            'model' => $dto->model,
            'settings' => $dto->settings,
            'is_active' => $dto->isActive,
            'created_by' => $dto->createdBy,
        ]);
    }
}
