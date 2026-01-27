<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for listing image folders
 */
class ListImageFoldersRequest extends FormRequest
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
