<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProxySettingsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'use_random_proxy' => $this->use_random_proxy,
            'apply_to_all_channels' => $this->apply_to_all_channels,
            'on_proxy_failure' => $this->on_proxy_failure,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
