<?php

declare(strict_types=1);

namespace Modules\StorageManagement\DTOs;

use Illuminate\Http\Request;

class StorageConfigDTO
{
    public function __construct(
        public string $driver,
        public ?string $key = null,
        public ?string $secret = null,
        public ?string $region = null,
        public ?string $bucket = null,
        public ?string $endpoint = null,
        public ?string $url = null,
        public bool $usePathStyleEndpoint = false,
        public ?string $rootPath = null,
        public array $metadata = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Check if fields are present in request (for updates, we want to know if field was sent)
        // For Laravel, if a field is not in request, input() returns null
        // If field is in request but empty, input() returns empty string
        // We'll use null to mean "not provided" and empty string to mean "provided but empty"
        
        return new self(
            driver: $request->input('driver'),
            key: $request->input('key'), // null if not provided, '' if empty, value if provided
            secret: $request->input('secret'),
            region: $request->input('region'),
            bucket: $request->input('bucket'),
            endpoint: $request->input('endpoint'),
            url: $request->input('url'),
            usePathStyleEndpoint: $request->boolean('use_path_style_endpoint', false),
            rootPath: $request->input('root_path'),
            metadata: $request->input('metadata', []),
        );
    }
}
