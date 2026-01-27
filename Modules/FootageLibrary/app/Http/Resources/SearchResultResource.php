<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'footage' => new FootageResource($this->resource['footage']),
            'score' => $this->resource['score'],
        ];
    }
}
