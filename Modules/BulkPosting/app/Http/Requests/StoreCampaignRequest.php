<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_name' => ['required', 'string', 'max:255'],
            'project_name' => ['required', 'string', 'max:255'],
            'brand_logo_storage_file_id' => ['nullable', 'integer', 'exists:storage_files,id'],
            'content_source_type' => ['required', 'string', 'in:csv_file,media_upload'],
            'content_source_config' => ['nullable', 'array'],
            'content_source_config.folder_ids' => ['nullable', 'array'],
            'content_source_config.folder_ids.*' => ['integer'],
            'content_source_config.csv_storage_file_id' => ['nullable', 'integer'],
            'schedule_condition' => ['required', 'string', 'in:minute,hourly,daily,weekly,monthly'],
            'schedule_interval' => ['required', 'integer', 'min:1'],
            'repost_enabled' => ['sometimes', 'boolean'],
            'repost_condition' => ['nullable', 'string', 'in:minute,hourly,daily,weekly,monthly'],
            'repost_interval' => ['nullable', 'integer', 'min:0'],
            'repost_max_count' => ['nullable', 'integer', 'min:1'],
            'connections' => ['required', 'array'],
            'connections.channels' => ['nullable', 'array'],
            'connections.channels.*' => ['integer'],
            'connections.groups' => ['nullable', 'array'],
            'connections.groups.*' => ['integer'],
        ];
    }
}
