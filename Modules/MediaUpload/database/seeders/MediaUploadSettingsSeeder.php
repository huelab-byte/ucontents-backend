<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\GeneralSettings\Models\GeneralSetting;

class MediaUploadSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'mediaupload.ai_provider' => [
                'value' => 'ucontents',
                'type' => 'string',
                'description' => 'Primary AI provider for MediaUpload',
            ],
            'mediaupload.vision_model' => [
                'value' => 'qwen2-vl-7b',
                'type' => 'string',
                'description' => 'Primary vision model for MediaUpload',
            ],
            'mediaupload.text_model' => [
                'value' => 'qwen2-vl-7b',
                'type' => 'string',
                'description' => 'Primary text model for MediaUpload',
            ],
        ];

        foreach ($settings as $key => $data) {
            // Only create if it doesn't exist to avoid overwriting user settings
            if (!GeneralSetting::where('key', $key)->exists()) {
                GeneralSetting::create([
                    'key' => $key,
                    'value' => $data['value'],
                    'type' => $data['type'],
                    'description' => $data['description'],
                ]);
            }
        }
    }
}
