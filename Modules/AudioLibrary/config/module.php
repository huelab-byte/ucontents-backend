<?php

declare(strict_types=1);

return [
    'name' => 'AudioLibrary',
    'enabled' => true,
    
    'features' => [
        'audio_upload' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'bulk_upload' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'folder_management' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'metadata_generation' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],
    
    'endpoints' => [
        'admin' => [
            'audio_stats' => ['enabled' => true],
            'view_all_audio' => ['enabled' => true],
            'delete_audio' => ['enabled' => true],
        ],
        'customer' => [
            'upload_audio' => ['enabled' => true],
            'bulk_upload' => ['enabled' => true],
            'manage_folders' => ['enabled' => true],
            'generate_metadata' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => [
            'view_all_audio',
            'delete_any_audio',
            'view_audio_stats',
        ],
        'customer' => [
            'upload_audio',
            'bulk_upload_audio',
            'view_audio',
            'manage_audio',
            'manage_audio_folders',
        ],
    ],
    
    'audio' => [
        'max_file_size' => env('AUDIO_MAX_SIZE', 102400), // 100MB in KB
        'allowed_formats' => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma'],
    ],
    
    'metadata' => [
        'ai_provider' => env('AUDIO_METADATA_AI_PROVIDER', 'openai'),
        'ai_model' => env('AUDIO_METADATA_AI_MODEL', 'gpt-4o'),
    ],
    
    'upload' => [
        'queue_name' => env('AUDIO_UPLOAD_QUEUE', 'audio-uploads'),
        'chunk_size' => env('AUDIO_CHUNK_SIZE', 10485760), // 10MB
        'max_concurrent' => env('AUDIO_MAX_CONCURRENT', 5),
    ],
];
