<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadImageOverlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('imageoverlay.module.upload.max_file_size', 50 * 1024 * 1024) / 1024;

        return [
            'files' => 'required|array|min:1|max:50',
            'files.*' => [
                'required',
                'file',
                'max:' . $maxSize,
                // Only formats that support transparency: PNG, GIF, WebP
                'mimes:png,gif,webp',
            ],
            'folder_id' => 'nullable|exists:image_overlay_folders,id',
        ];
    }
}
