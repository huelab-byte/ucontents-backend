<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkFileUploadRequest extends FormRequest
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
            'files' => 'required|array|max:50',
            'files.*' => 'required|file|max:' . $maxSize,
            'folder' => 'nullable|string|max:255',
        ];
    }
}
