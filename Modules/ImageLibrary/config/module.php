<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | ImageLibrary Module Configuration
    |--------------------------------------------------------------------------
    */
    
    'name' => 'ImageLibrary',
    'enabled' => true,
    
    'features' => [
        'image_upload' => [
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
            'image_stats' => ['enabled' => true],
            'view_all_images' => ['enabled' => true],
            'delete_image' => ['enabled' => true],
        ],
        'customer' => [
            'upload_image' => ['enabled' => true],
            'bulk_upload' => ['enabled' => true],
            'manage_folders' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => [
            'view_all_images',
            'delete_any_image',
            'view_image_stats',
        ],
        'customer' => [
            'upload_image',
            'bulk_upload_image',
            'view_image',
            'manage_image',
            'manage_image_folders',
        ],
    ],
    
    'upload' => [
        'max_file_size' => 50 * 1024 * 1024, // 50MB
        'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'],
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png', 
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml',
        ],
    ],
];
