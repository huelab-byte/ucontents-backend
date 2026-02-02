<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaUploadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'folder_id' => $this->folder_id,
            'youtube_heading' => $this->youtube_heading,
            'social_caption' => $this->social_caption,
            'hashtags' => $this->hashtags,
            'video_metadata' => $this->video_metadata,
            'loop_count' => $this->loop_count,
            'enable_reverse' => $this->enable_reverse,
            'processed_at' => $this->processed_at,
            'storage_file' => $this->whenLoaded('storageFile', fn () => [
                'id' => $this->storageFile->id,
                'url' => $this->storageFile->url,
                'path' => $this->storageFile->path,
                'size' => $this->storageFile->size,
                'mime_type' => $this->storageFile->mime_type,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
