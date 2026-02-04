<?php

declare(strict_types=1);

return [
    'name' => 'FootageLibrary',
    'enabled' => true,
    
    'features' => [
        'footage_upload' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'bulk_upload' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'ai_search' => [
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
            'footage_stats' => ['enabled' => true],
            'view_all_footage' => ['enabled' => true],
            'delete_footage' => ['enabled' => true],
        ],
        'customer' => [
            'upload_footage' => ['enabled' => true],
            'bulk_upload' => ['enabled' => true],
            'search_footage' => ['enabled' => true],
            'manage_folders' => ['enabled' => true],
            'generate_metadata' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'admin' => [
            'view_all_footage',
            'delete_any_footage',
            'view_footage_stats',
        ],
        'customer' => [
            'upload_footage',
            'bulk_upload_footage',
            'view_footage',
            'manage_footage',
            'search_footage',
            'manage_footage_folders',
        ],
    ],
    
    'qdrant' => [
        'url' => env('QDRANT_URL', 'http://localhost:6333'),
        'api_key' => env('QDRANT_API_KEY', null),
        'collection_name' => env('QDRANT_FOOTAGE_COLLECTION', 'footage_embeddings'),
        'vector_size' => 1536, // OpenAI ada-002 embedding size
    ],
    
    'video' => [
        'max_file_size' => env('FOOTAGE_MAX_SIZE', 1024000), // 1GB in KB
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
    
    'metadata' => [
        'ai_provider' => env('FOOTAGE_METADATA_AI_PROVIDER', 'openai'),
        'ai_model' => env('FOOTAGE_METADATA_AI_MODEL', 'gpt-4o'),
        'vision_model' => env('FOOTAGE_VISION_MODEL', 'gpt-4o'), // gpt-4o supports vision
        'embedding_model' => env('FOOTAGE_EMBEDDING_MODEL', 'text-embedding-ada-002'),
        // Fallback providers/models to try if primary fails
        'vision_fallbacks' => [
            ['provider' => 'openai', 'model' => 'gpt-4o'],
            ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
            ['provider' => 'openai', 'model' => 'gpt-4-turbo'],
            ['provider' => 'anthropic', 'model' => 'claude-3-5-sonnet-20241022'],
            ['provider' => 'google', 'model' => 'gemini-1.5-flash'],
            ['provider' => 'ucontents', 'model' => 'moondream2'], // Self-hosted vision
        ],
        'text_fallbacks' => [
            ['provider' => 'openai', 'model' => 'gpt-4o'],
            ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
            ['provider' => 'anthropic', 'model' => 'claude-3-5-sonnet-20241022'],
            ['provider' => 'ucontents', 'model' => 'mistral-7b-instruct'], // Self-hosted text
        ],
    ],
    
    'upload' => [
        'queue_name' => env('FOOTAGE_UPLOAD_QUEUE', 'footage-uploads'),
        'chunk_size' => env('FOOTAGE_CHUNK_SIZE', 10485760), // 10MB
        'max_concurrent' => env('FOOTAGE_MAX_CONCURRENT', 5),
    ],
    
    'search' => [
        'max_results' => env('FOOTAGE_SEARCH_MAX_RESULTS', 1000),
        'diversity_threshold' => env('FOOTAGE_SEARCH_DIVERSITY_THRESHOLD', 0.7),
        'min_similarity' => env('FOOTAGE_SEARCH_MIN_SIMILARITY', 0.5),
    ],
];
