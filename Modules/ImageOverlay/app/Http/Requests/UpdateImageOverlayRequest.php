<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImageOverlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'folder_id' => 'sometimes|nullable|exists:image_overlay_folders,id',
            'metadata' => 'sometimes|array',
            'metadata.description' => 'sometimes|string|max:1000',
            'metadata.tags' => 'sometimes|array',
            'metadata.tags.*' => 'string|max:50',
        ];
    }
}
