<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('imagelibrary.module.upload.max_file_size', 50 * 1024 * 1024) / 1024;

        return [
            'files' => 'required|array|min:1|max:50',
            'files.*' => [
                'required',
                'file',
                'max:' . $maxSize,
                'mimes:jpg,jpeg,png,gif,webp,bmp,svg',
            ],
            'folder_id' => 'nullable|exists:image_folders,id',
        ];
    }
}
