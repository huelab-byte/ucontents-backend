<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_source_type' => 'sometimes|in:prompt,frames,title',
            'ai_prompt_template_id' => 'sometimes|nullable|integer',
            'custom_prompt' => 'sometimes|nullable|string|max:10000',
            'heading_length' => 'sometimes|integer|min:0',
            'heading_emoji' => 'sometimes|boolean',
            'caption_length' => 'sometimes|integer|min:0',
            'hashtag_count' => 'sometimes|integer|min:1|max:30',
            'default_caption_template_id' => 'sometimes|nullable|integer|exists:media_upload_caption_templates,id',
            'default_loop_count' => 'sometimes|integer|min:1|max:100',
            'default_enable_reverse' => 'sometimes|boolean',
        ];
    }
}
