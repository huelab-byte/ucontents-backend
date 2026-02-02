<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CaptionTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'font' => $this->font,
            'font_size' => $this->font_size,
            'font_weight' => $this->font_weight ?? 'regular',
            'font_color' => $this->font_color,
            'outline_color' => $this->outline_color,
            'outline_size' => $this->outline_size,
            'position' => $this->position,
            'position_offset' => (int) ($this->position_offset ?? 30),
            'words_per_caption' => $this->words_per_caption,
            'word_highlighting' => $this->word_highlighting,
            'highlight_color' => $this->highlight_color,
            'highlight_style' => $this->highlight_style,
            'background_opacity' => $this->background_opacity,
            'enable_alternating_loop' => $this->enable_alternating_loop,
            'loop_count' => $this->loop_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
