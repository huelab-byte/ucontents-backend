<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \Modules\NotificationManagement\Models\Notification $resource
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'severity' => $this->severity,
            'created_by_user_id' => $this->created_by_user_id,
            'created_at' => optional($this->created_at)->toISOString(),
            'url' => $this->resource->getUrl(),
        ];
    }
}

