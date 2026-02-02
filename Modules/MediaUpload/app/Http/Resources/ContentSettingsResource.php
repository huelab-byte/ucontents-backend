<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentSettingsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'folder_id' => $this->folder_id,
            'content_source_type' => $this->content_source_type,
            'ai_prompt_template_id' => $this->ai_prompt_template_id,
            'custom_prompt' => $this->custom_prompt,
            'heading_length' => $this->heading_length,
            'heading_emoji' => $this->heading_emoji,
            'caption_length' => $this->caption_length,
            'hashtag_count' => $this->hashtag_count,
            'default_caption_template_id' => $this->default_caption_template_id,
            'default_loop_count' => $this->default_loop_count,
            'default_enable_reverse' => $this->default_enable_reverse,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
