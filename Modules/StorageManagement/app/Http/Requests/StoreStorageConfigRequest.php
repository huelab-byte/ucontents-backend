<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStorageConfigRequest extends FormRequest
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
            'driver' => 'required|string|in:local,do_s3,aws_s3,contabo_s3,cloudflare_r2,backblaze_b2',
            'is_active' => 'nullable|boolean',
            'key' => 'required_unless:driver,local|string',
            'secret' => 'required_unless:driver,local|string',
            'region' => 'required_unless:driver,local|string',
            'bucket' => 'required_unless:driver,local|string',
            'endpoint' => 'nullable|string|url',
            'url' => 'nullable|string|url',
            'use_path_style_endpoint' => 'nullable|boolean',
            'root_path' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
