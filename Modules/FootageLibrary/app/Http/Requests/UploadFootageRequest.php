<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFootageRequest extends FormRequest
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
            'file' => 'required|file|max:' . $maxSize . '|mimes:' . implode(',', $allowedFormats),
            'folder_id' => 'nullable|integer|exists:footage_folders,id',
            'title' => 'nullable|string|max:255',
            'metadata_source' => 'nullable|string|in:title,frames,manual,none',
        ];
    }
}
