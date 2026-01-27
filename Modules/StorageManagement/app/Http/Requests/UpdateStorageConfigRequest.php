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
            'name' => 'sometimes|string|max:255',
            'is_active' => 'nullable|boolean',
            'config' => 'sometimes|array',
            'config.key' => 'sometimes|string',
            'config.secret' => 'sometimes|string',
            'config.region' => 'sometimes|string',
            'config.bucket' => 'sometimes|string',
            'config.endpoint' => 'nullable|string|url',
            'config.path' => 'nullable|string',
        ];
    }
}
