<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialProviderAppResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'enabled' => (bool) $this->enabled,
            // Never return secrets; only indicate if configured.
            'client_id' => $this->client_id,
            'has_client_secret' => !empty($this->client_secret),
            'scopes' => $this->scopes ?? [],
            'extra' => $this->extra ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

