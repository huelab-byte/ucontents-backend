<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBgmRequest extends FormRequest
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
            'file' => 'required|file|max:' . $maxSize . '|mimes:' . implode(',', $allowedFormats),
            'folder_id' => 'nullable|integer|exists:bgm_folders,id',
            'title' => 'nullable|string|max:255',
            'metadata_source' => 'nullable|string|in:title,manual,none',
        ];
    }
}
