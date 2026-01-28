<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageOverlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('imageoverlay.module.upload.max_file_size', 50 * 1024 * 1024) / 1024; // Convert to KB

        return [
            'file' => [
                'required',
                'file',
                'max:' . $maxSize,
                // Only formats that support transparency: PNG, GIF, WebP
                'mimes:png,gif,webp',
            ],
            'folder_id' => 'nullable|exists:image_overlay_folders,id',
            'title' => 'nullable|string|max:255',
        ];
    }
}
