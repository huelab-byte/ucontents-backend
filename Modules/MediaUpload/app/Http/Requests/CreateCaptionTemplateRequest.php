<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCaptionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'font' => 'sometimes|string|max:100',
            'font_size' => 'sometimes|integer|min:8|max:200',
            'font_weight' => 'sometimes|string|in:regular,bold,italic,bold_italic,black',
            'font_color' => 'sometimes|string|max:20',
            'outline_color' => 'sometimes|string|max:20',
            'outline_size' => 'sometimes|integer|min:0|max:20',
            'position' => 'sometimes|in:top,center,bottom',
            'position_offset' => 'sometimes|integer|min:0|max:500',
            'words_per_caption' => 'sometimes|integer|min:1|max:20',
            'word_highlighting' => 'sometimes|boolean',
            'highlight_color' => 'sometimes|nullable|string|max:20',
            'highlight_style' => 'sometimes|in:text,background',
            'background_opacity' => 'sometimes|integer|min:0|max:100',
            'enable_alternating_loop' => 'sometimes|boolean',
            'loop_count' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
