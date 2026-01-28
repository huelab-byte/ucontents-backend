<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'path' => $this->path,
            'parent' => $this->whenLoaded('parent', fn() => new FolderResource($this->parent)),
            'children' => $this->whenLoaded('children', fn() => FolderResource::collection($this->children)),
            'video_overlay_count' => $this->whenCounted('videoOverlays'),
            'horizontal_count' => $this->when(isset($this->horizontal_count), $this->horizontal_count ?? 0),
            'vertical_count' => $this->when(isset($this->vertical_count), $this->vertical_count ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
