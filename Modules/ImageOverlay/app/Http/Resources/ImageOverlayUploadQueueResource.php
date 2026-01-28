<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageOverlayUploadQueueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'status' => $this->status,
            'progress' => $this->progress,
            'error_message' => $this->error_message,
            'image_overlay_id' => $this->image_overlay_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
