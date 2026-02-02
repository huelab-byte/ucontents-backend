<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;
        $parentId = $this->input('parent_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('media_upload_folders', 'name')
                    ->where('user_id', $userId)
                    ->where('parent_id', $parentId),
            ],
            'parent_id' => 'nullable|integer|exists:media_upload_folders,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A folder with this name already exists.',
        ];
    }
}
