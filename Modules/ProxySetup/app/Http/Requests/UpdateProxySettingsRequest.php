<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProxySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'use_random_proxy' => ['sometimes', 'boolean'],
            'apply_to_all_channels' => ['sometimes', 'boolean'],
            'on_proxy_failure' => ['sometimes', 'string', 'in:stop_automation,continue_without_proxy'],
        ];
    }

    public function messages(): array
    {
        return [
            'on_proxy_failure.in' => 'The proxy failure action must be either "stop_automation" or "continue_without_proxy".',
        ];
    }
}
