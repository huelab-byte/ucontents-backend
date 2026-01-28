<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BgmUploadQueueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'status' => $this->status,
            'progress' => $this->progress,
            'error_message' => $this->error_message,
            'bgm_id' => $this->bgm_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
