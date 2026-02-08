<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cc = $this->input('caption_config');
        if (is_string($cc)) {
            $decoded = json_decode($cc, true);
            $this->merge(['caption_config' => is_array($decoded) ? $decoded : null]);
        }
    }

    public function rules(): array
    {
        $maxSize = (config('mediaupload.module.video.max_file_size', 1024000) ?? 1024000) * 1024;
        $formats = config('mediaupload.module.video.allowed_formats', ['mp4', 'mov', 'avi', 'mkv', 'webm']);
        $mimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm'];
        $folderExistsForUser = Rule::exists('media_upload_folders', 'id')->where('user_id', $this->user()?->id());
        $maxFiles = (int) (config('mediaupload.module.upload.max_files_per_bulk', 1000) ?: 1000);

        return [
            'files' => 'required|array|min:1|max:' . $maxFiles,
            'files.*' => 'required|file|max:' . $maxSize . '|mimetypes:' . implode(',', $mimes),
            'folder_id' => ['required_without:folder_ids', 'nullable', 'integer', $folderExistsForUser],
            'folder_ids' => ['required_without:folder_id', 'nullable', 'array'],
            'folder_ids.*' => ['required', 'integer', $folderExistsForUser],
            'caption_config' => 'sometimes|nullable|array',
            'caption_config.enable_video_caption' => 'sometimes|nullable|boolean',
            'caption_config.font' => 'sometimes|nullable|string|max:100',
            'caption_config.font_size' => 'sometimes|nullable|integer|min:12|max:120',
            'caption_config.font_weight' => 'sometimes|nullable|string|in:regular,bold,italic,bold_italic,black',
            'caption_config.font_color' => 'sometimes|nullable|string|max:20',
            'caption_config.outline_color' => 'sometimes|nullable|string|max:20',
            'caption_config.outline_size' => 'sometimes|nullable|integer|min:0|max:20',
            'caption_config.position' => 'sometimes|nullable|string|in:top,center,bottom',
            'caption_config.position_offset' => 'sometimes|nullable|integer|min:0|max:500',
            'caption_config.words_per_caption' => 'sometimes|nullable|integer|min:1|max:20',
            'caption_config.loop_count' => 'sometimes|nullable|integer|min:1|max:10',
            'caption_config.enable_reverse' => 'sometimes|nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $folderIds = $this->input('folder_ids');
            $files = $this->file('files');
            if (is_array($folderIds) && $files !== null) {
                $countFiles = is_array($files) ? count($files) : 0;
                if (count($folderIds) !== $countFiles) {
                    $validator->errors()->add(
                        'folder_ids',
                        "folder_ids must have the same length as files ({$countFiles} files)."
                    );
                }
            }
        });
    }
}
