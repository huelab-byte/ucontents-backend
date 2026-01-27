<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for listing social connection groups
 */
class ListGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
