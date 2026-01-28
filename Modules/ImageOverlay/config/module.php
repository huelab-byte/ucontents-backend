<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | ImageOverlay Module Configuration
    |--------------------------------------------------------------------------
    */
    
    'name' => 'ImageOverlay',
    'enabled' => true,
    
    'features' => [
        'image_overlay_upload' => [
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
    ],
    
    'endpoints' => [
        'admin' => [
            'image_overlay_stats' => ['enabled' => true],
            'view_all_image_overlays' => ['enabled' => true],
            'delete_image_overlay' => ['enabled' => true],
        ],
        'customer' => [
            'upload_image_overlay' => ['enabled' => true],
            'bulk_upload' => ['enabled' => true],
            'manage_folders' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => [
            'view_all_image_overlay',
            'delete_any_image_overlay',
            'view_image_overlay_stats',
        ],
        'customer' => [
            'upload_image_overlay',
            'bulk_upload_image_overlay',
            'view_image_overlay',
            'manage_image_overlay',
            'manage_image_overlay_folders',
        ],
    ],
    
    'upload' => [
        'max_file_size' => 50 * 1024 * 1024, // 50MB
        // Only formats that support transparency: PNG, GIF, WebP
        'allowed_formats' => ['png', 'gif', 'webp'],
        'allowed_mime_types' => [
            'image/png',
            'image/gif',
            'image/webp',
        ],
    ],
];
