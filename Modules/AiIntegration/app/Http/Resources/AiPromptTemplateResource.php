<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for AI Prompt Template
 */
class AiPromptTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'template' => $this->template,
            'variables' => $this->variables,
            'category' => $this->category,
            'provider_slug' => $this->provider_slug,
            'model' => $this->model,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
