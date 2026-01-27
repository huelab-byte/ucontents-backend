<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StorageSettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'driver' => $this->driver,
            'is_active' => $this->is_active,
            'key' => $this->when($request->user()?->isAdmin(), $this->key),
            'secret' => $this->when(false, null), // Never expose secret
            'region' => $this->region,
            'bucket' => $this->bucket,
            'endpoint' => $this->endpoint,
            'url' => $this->url,
            'use_path_style_endpoint' => $this->use_path_style_endpoint,
            'root_path' => $this->root_path,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
