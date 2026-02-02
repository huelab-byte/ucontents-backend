<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UploadQueueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'status' => $this->status,
            'progress' => $this->progress,
            'error_message' => $this->error_message,
            'media_upload_id' => $this->media_upload_id,
            'created_at' => $this->created_at,
        ];
    }
}
