<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageOverlayResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'metadata' => $this->metadata,
            'status' => $this->status,
            'folder_id' => $this->folder_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'storage_file' => $this->whenLoaded('storageFile', function () {
                return [
                    'id' => $this->storageFile->id,
                    'url' => $this->storageFile->url,
                    'path' => $this->storageFile->path,
                    'size' => $this->storageFile->size,
                    'mime_type' => $this->storageFile->mime_type,
                ];
            }),
            'folder' => $this->whenLoaded('folder', function () {
                return new FolderResource($this->folder);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
