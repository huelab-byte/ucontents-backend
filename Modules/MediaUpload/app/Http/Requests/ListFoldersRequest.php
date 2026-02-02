<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListFoldersRequest extends FormRequest
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
