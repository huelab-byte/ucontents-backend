<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProxyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:http,https,socks4,socks5'],
            'host' => ['sometimes', 'string', 'max:255'],
            'port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The proxy type must be one of: http, https, socks4, socks5.',
            'port.integer' => 'The proxy port must be a number.',
            'port.min' => 'The proxy port must be at least 1.',
            'port.max' => 'The proxy port cannot exceed 65535.',
        ];
    }
}
