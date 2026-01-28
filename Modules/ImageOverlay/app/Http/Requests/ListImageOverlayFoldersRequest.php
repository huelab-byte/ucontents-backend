<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for listing image overlay folders
 */
class ListImageOverlayFoldersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
