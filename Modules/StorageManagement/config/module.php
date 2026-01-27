<?php

declare(strict_types=1);

return [
    'name' => 'StorageManagement',
    'enabled' => true,
    
    'features' => [
        'storage_configuration' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'storage_migration' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'file_upload' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'storage_cleanup' => [
            'enabled' => true,
            'admin_only' => true,
        ],
        'storage_analytics' => [
            'enabled' => true,
            'admin_only' => true,
        ],
    ],
    
    'endpoints' => [
        'admin' => [
            'storage_config' => ['enabled' => true],
            'storage_migration' => ['enabled' => true],
            'storage_usage' => ['enabled' => true],
            'storage_cleanup' => ['enabled' => true],
            'test_connection' => ['enabled' => true],
        ],
        'customer' => [
            'upload_file' => ['enabled' => true],
            'bulk_upload' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => [
            'manage_storage',
            'migrate_storage',
            'view_storage_analytics',
            'cleanup_storage',
        ],
        'customer' => [
            'upload_files',
            'bulk_upload_files',
        ],
    ],
    
    'supported_drivers' => [
        'local',
        'do_s3',         // DigitalOcean Spaces
        'aws_s3',        // AWS S3
        'contabo_s3',    // Contabo Object Storage
        'cloudflare_r2', // Cloudflare R2
        'backblaze_b2',  // Backblaze B2
    ],
    
    'default_driver' => env('STORAGE_DRIVER', 'local'),
    
    'upload' => [
        'max_file_size' => env('MAX_UPLOAD_SIZE', 102400), // 100MB in KB
        'allowed_mime_types' => env('ALLOWED_MIME_TYPES', 'image/*,video/*,audio/*,application/pdf'),
        'queue_name' => env('STORAGE_UPLOAD_QUEUE', 'default'),
        'chunk_size' => env('STORAGE_CHUNK_SIZE', 5242880), // 5MB
    ],
];
