<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn () => new FolderResource($this->parent)),
            'children' => $this->whenLoaded('children', fn () => FolderResource::collection($this->children)),
            'media_uploads_count' => $this->whenCounted('mediaUploads'),
            'content_settings' => $this->whenLoaded('contentSettings', function () {
                $s = $this->contentSettings;
                return $s ? new \Modules\MediaUpload\Http\Resources\ContentSettingsResource($s) : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
