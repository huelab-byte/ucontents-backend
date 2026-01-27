<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \Modules\NotificationManagement\Models\NotificationRecipient $resource
 */
class NotificationRecipientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'notification_id' => $this->notification_id,
            'read_at' => optional($this->read_at)->toISOString(),
            'delivered_email_at' => optional($this->delivered_email_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'notification' => new NotificationResource($this->whenLoaded('notification')),
        ];
    }
}

