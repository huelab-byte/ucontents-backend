<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'smtp_configuration_id' => $this->smtp_configuration_id,
            'email_template_id' => $this->email_template_id,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => $this->subject,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'sent_at' => $this->sent_at,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
