<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Http\Resources;

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
            'bgm_count' => $this->whenCounted('bgm'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
