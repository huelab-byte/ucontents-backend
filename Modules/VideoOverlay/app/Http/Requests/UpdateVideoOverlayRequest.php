<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoOverlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'folder_id' => 'sometimes|nullable|integer|exists:video_overlay_folders,id',
        ];
    }
}
