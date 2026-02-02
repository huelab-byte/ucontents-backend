<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProxyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:http,https,socks4,socks5'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A proxy name is required.',
            'type.required' => 'The proxy type is required.',
            'type.in' => 'The proxy type must be one of: http, https, socks4, socks5.',
            'host.required' => 'The proxy host is required.',
            'port.required' => 'The proxy port is required.',
            'port.integer' => 'The proxy port must be a number.',
            'port.min' => 'The proxy port must be at least 1.',
            'port.max' => 'The proxy port cannot exceed 65535.',
        ];
    }
}
