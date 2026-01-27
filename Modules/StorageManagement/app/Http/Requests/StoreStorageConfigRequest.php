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
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'config' => 'required|array',
            'config.key' => 'required_unless:driver,local|string',
            'config.secret' => 'required_unless:driver,local|string',
            'config.region' => 'required_unless:driver,local|string',
            'config.bucket' => 'required_unless:driver,local|string',
            'config.endpoint' => 'nullable|string|url',
            'config.path' => 'nullable|string',
        ];
    }
}
