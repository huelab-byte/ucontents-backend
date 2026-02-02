<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'youtube_heading' => 'sometimes|nullable|string|max:500',
            'social_caption' => 'sometimes|nullable|string|max:10000',
            'hashtags' => 'sometimes|nullable|array',
            'hashtags.*' => 'string|max:200',
        ];
    }
}
