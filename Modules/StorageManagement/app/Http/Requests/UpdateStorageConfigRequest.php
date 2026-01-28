<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorageConfigRequest extends FormRequest
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
        return [
            'driver' => 'sometimes|string|in:local,do_s3,aws_s3,contabo_s3,cloudflare_r2,backblaze_b2',
            'is_active' => 'nullable|boolean',
            'key' => 'sometimes|string',
            'secret' => 'sometimes|string',
            'region' => 'sometimes|string',
            'bucket' => 'sometimes|string',
            'endpoint' => 'nullable|string|url',
            'url' => 'nullable|string|url',
            'use_path_style_endpoint' => 'nullable|boolean',
            'root_path' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
