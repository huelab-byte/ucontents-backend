<?php

declare(strict_types=1);

return [
    'name' => 'VideoOverlay',
    'enabled' => true,
    
    'features' => [
        'video_overlay_upload' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'folder_management' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],
    
    'endpoints' => [
        'admin' => [
            'video_overlay_stats' => ['enabled' => true],
            'view_all_video_overlays' => ['enabled' => true],
            'delete_video_overlay' => ['enabled' => true],
        ],
        'customer' => [
            'upload_video_overlay' => ['enabled' => true],
            'manage_folders' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => [
            'view_all_video_overlay',
            'delete_any_video_overlay',
            'view_video_overlay_stats',
        ],
        'customer' => [
            'upload_video_overlay',
            'view_video_overlay',
            'manage_video_overlay',
            'manage_video_overlay_folders',
        ],
    ],
    
    'video' => [
        'max_file_size' => env('VIDEO_OVERLAY_MAX_SIZE', 1024000), // 1GB in KB
        'allowed_formats' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
        'ffmpeg' => [
            'threads' => env('FFMPEG_THREADS', 2),
            'timeout' => env('FFMPEG_TIMEOUT', 300),
        ],
    ],
];
