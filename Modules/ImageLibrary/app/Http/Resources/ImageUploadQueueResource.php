<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageUploadQueueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'status' => $this->status,
            'progress' => $this->progress,
            'error_message' => $this->error_message,
            'image_id' => $this->image_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
