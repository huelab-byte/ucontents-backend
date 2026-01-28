<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadBgmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('bgmlibrary.module.audio.max_file_size', 102400) * 1024;
        $allowedFormats = config('bgmlibrary.module.audio.allowed_formats', ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma']);

        return [
            'files' => 'required|array|min:1|max:50',
            'files.*' => 'required|file|max:' . $maxSize . '|mimes:' . implode(',', $allowedFormats),
            'folder_id' => 'nullable|integer|exists:bgm_folders,id',
            'metadata_source' => 'nullable|string|in:title,manual',
        ];
    }
}
