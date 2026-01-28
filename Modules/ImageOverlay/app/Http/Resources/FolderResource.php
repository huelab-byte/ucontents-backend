<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Resources;

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
            'parent' => $this->whenLoaded('parent', function () {
                return new FolderResource($this->parent);
            }),
            'children' => $this->whenLoaded('children', function () {
                return FolderResource::collection($this->children);
            }),
            'image_overlay_count' => $this->when(isset($this->image_overlays_count), $this->image_overlays_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
