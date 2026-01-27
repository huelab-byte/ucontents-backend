<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SocialConnection\Models\SocialConnectionGroup;

/**
 * @mixin SocialConnectionGroup
 */
class SocialConnectionGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
