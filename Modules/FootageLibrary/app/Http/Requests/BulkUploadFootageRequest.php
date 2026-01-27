<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadFootageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('footagelibrary.module.video.max_file_size', 1024000) * 1024;
        $allowedFormats = config('footagelibrary.module.video.allowed_formats', ['mp4', 'mov', 'avi', 'mkv', 'webm']);

        return [
            'files' => 'required|array|min:1|max:50',
            'files.*' => 'required|file|max:' . $maxSize . '|mimes:' . implode(',', $allowedFormats),
            'folder_id' => 'nullable|integer|exists:footage_folders,id',
            'metadata_source' => 'nullable|string|in:title,frames,manual',
        ];
    }
}
