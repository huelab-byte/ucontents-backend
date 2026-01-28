<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListVideoOverlayFoldersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'sometimes|nullable|integer|exists:video_overlay_folders,id',
        ];
    }
}
