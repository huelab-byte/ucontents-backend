<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxSize = config('storagemanagement.upload.max_file_size', 102400) * 1024;

        return [
            'file' => 'required|file|max:' . $maxSize,
            'path' => 'nullable|string|max:255',
            'folder' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
