<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for AI Provider
 */
class AiProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'supported_models' => $this->supported_models,
            'base_url' => $this->base_url,
            'is_active' => $this->is_active,
            'has_active_keys' => $this->hasActiveKeys(),
            'active_keys_count' => $this->activeApiKeys()->count(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
