<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\GeneralSettings\Models\GeneralSetting;

class MediaUploadSettingsSeeder extends Seeder
{
    /**
     * Media upload content generation uses only AI Integration (API keys, scopes, priority).
     * Provider order comes from config: mediaupload.module.content_generation text_fallbacks / vision_fallbacks.
     * No General Settings entries are needed for AI provider choice.
     */
    public function run(): void
    {
        // Optional: remove legacy mediaupload.* keys if they exist (no longer used)
        GeneralSetting::whereIn('key', [
            'mediaupload.ai_provider',
            'mediaupload.vision_model',
            'mediaupload.text_model',
        ])->delete();
    }
}
