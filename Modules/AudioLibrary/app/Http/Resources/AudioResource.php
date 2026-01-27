<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AudioResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'metadata' => $this->metadata,
            'status' => $this->status,
            'folder_id' => $this->folder_id,
            'folder' => $this->whenLoaded('folder', fn() => new FolderResource($this->folder)),
            'storage_file' => $this->whenLoaded('storageFile', fn() => [
                'id' => $this->storageFile->id,
                'url' => $this->storageFile->url,
                'path' => $this->storageFile->path,
                'size' => $this->storageFile->size,
                'mime_type' => $this->storageFile->mime_type,
            ]),
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
