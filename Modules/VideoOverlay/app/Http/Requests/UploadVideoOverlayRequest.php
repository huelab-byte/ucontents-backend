<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVideoOverlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('videooverlay.module.video.max_file_size', 1024000) * 1024;
        $allowedFormats = config('videooverlay.module.video.allowed_formats', ['mp4', 'mov', 'avi', 'mkv', 'webm']);

        return [
            'file' => 'required|file|max:' . $maxSize . '|mimes:' . implode(',', $allowedFormats),
            'folder_id' => 'nullable|integer|exists:video_overlay_folders,id',
            'title' => 'nullable|string|max:255',
        ];
    }
}
