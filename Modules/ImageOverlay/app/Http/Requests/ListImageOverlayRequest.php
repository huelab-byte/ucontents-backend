<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for listing image overlays
 */
class ListImageOverlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'folder_id' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', 'string', 'in:pending,processing,ready,failed'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
