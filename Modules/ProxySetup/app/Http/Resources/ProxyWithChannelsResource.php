<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProxyWithChannelsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'host' => $this->host,
            'port' => $this->port,
            'has_auth' => !empty($this->username),
            'is_enabled' => $this->is_enabled,
            'last_checked_at' => $this->last_checked_at?->toISOString(),
            'last_check_status' => $this->last_check_status,
            'last_check_message' => $this->last_check_message,
            'channels' => $this->whenLoaded('channels', function () {
                return $this->channels->map(function ($channel) {
                    return [
                        'id' => $channel->id,
                        'provider' => $channel->provider,
                        'type' => $channel->type,
                        'name' => $channel->name,
                        'username' => $channel->username,
                        'avatar_url' => $channel->avatar_url,
                        'is_active' => $channel->is_active,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
