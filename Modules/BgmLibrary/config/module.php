<?php

declare(strict_types=1);

return [
    'name' => 'BgmLibrary',
    'enabled' => true,
    
    'features' => [
        'bgm_upload' => [
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
            'bgm_stats' => ['enabled' => true],
            'view_all_bgm' => ['enabled' => true],
            'delete_bgm' => ['enabled' => true],
        ],
        'customer' => [
            'upload_bgm' => ['enabled' => true],
            'bulk_upload' => ['enabled' => true],
            'manage_folders' => ['enabled' => true],
            'generate_metadata' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => [
            'view_all_bgm',
            'delete_any_bgm',
            'view_bgm_stats',
        ],
        'customer' => [
            'upload_bgm',
            'bulk_upload_bgm',
            'view_bgm',
            'manage_bgm',
            'manage_bgm_folders',
        ],
    ],
    
    'audio' => [
        'max_file_size' => env('BGM_MAX_SIZE', 102400), // 100MB in KB
        'allowed_formats' => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma'],
    ],
    
    'metadata' => [
        'ai_provider' => env('BGM_METADATA_AI_PROVIDER', 'openai'),
        'ai_model' => env('BGM_METADATA_AI_MODEL', 'gpt-4o'),
    ],
    
    'upload' => [
        'queue_name' => env('BGM_UPLOAD_QUEUE', 'bgm-uploads'),
        'chunk_size' => env('BGM_CHUNK_SIZE', 10485760), // 10MB
        'max_concurrent' => env('BGM_MAX_CONCURRENT', 5),
    ],
];
