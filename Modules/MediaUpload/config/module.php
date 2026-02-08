<?php

declare(strict_types=1);

return [
    'name' => 'MediaUpload',
    'enabled' => true,

    'features' => [
        'folder_management' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'caption_templates' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'content_generation' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'bulk_upload' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],

    'endpoints' => [
        'admin' => [],
        'customer' => [
            'folders' => ['enabled' => true],
            'caption_templates' => ['enabled' => true],
            'content_settings' => ['enabled' => true],
            'uploads' => ['enabled' => true],
            'bulk_upload' => ['enabled' => true],
            'queue' => ['enabled' => true],
        ],
    ],

    'permissions' => [
        'admin' => [],
        'customer' => [
            'view_media_upload_folders',
            'manage_media_upload_folders',
            'upload_media',
            'manage_media_uploads',
            'manage_caption_templates',
        ],
    ],

    'video' => [
        'max_file_size' => env('MEDIA_UPLOAD_MAX_SIZE', 1024000), // 1GB in KB
        'allowed_formats' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
        'frame_extraction' => [
            'count' => 6,
            'quality' => 'high',
        ],
        'ffmpeg' => [
            'threads' => env('FFMPEG_THREADS', 2),
            'timeout' => env('FFMPEG_TIMEOUT', 300),
        ],
    ],

    'content_generation' => [
        'ai_provider' => env('MEDIA_UPLOAD_AI_PROVIDER', 'openai'),
        'vision_model' => env('MEDIA_UPLOAD_VISION_MODEL', 'gpt-4o'),
        'text_model' => env('MEDIA_UPLOAD_TEXT_MODEL', 'gpt-4o'),
        'vision_fallbacks' => [
            ['provider' => 'azure_openai', 'model' => 'gpt-4o'],   // Azure first (vision-capable)
            ['provider' => 'openai', 'model' => 'gpt-4o'],
            ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
            ['provider' => 'anthropic', 'model' => 'claude-3-5-sonnet-20241022'],
            ['provider' => 'google', 'model' => 'gemini-1.5-flash'],
            ['provider' => 'ucontents', 'model' => 'qwen2-vl-7b'],
            ['provider' => 'ucontents', 'model' => 'moondream2'],
        ],
        'text_fallbacks' => [
            ['provider' => 'azure_openai', 'model' => 'gpt-4o'],   // Azure first
            ['provider' => 'openai', 'model' => 'gpt-4o'],
            ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
            ['provider' => 'anthropic', 'model' => 'claude-3-5-sonnet-20241022'],
            ['provider' => 'ucontents', 'model' => 'qwen2-vl-7b'],
            ['provider' => 'ucontents', 'model' => 'mistral-7b-instruct'],
        ],
    ],

    'upload' => [
        'queue_name' => env('MEDIA_UPLOAD_QUEUE', 'default'),
        'temp_disk' => 'local',
        'temp_path' => 'temp/media-uploads',
        'max_files_per_bulk' => (int) (env('MEDIA_UPLOAD_MAX_FILES', 1000) ?: 1000),
    ],
];
