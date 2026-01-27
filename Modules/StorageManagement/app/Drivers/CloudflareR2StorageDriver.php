<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Drivers;

class CloudflareR2StorageDriver extends S3StorageDriver
{
    public function __construct(array $config)
    {
        // Cloudflare R2 uses S3-compatible API
        // Endpoint format: https://<account_id>.r2.cloudflarestorage.com
        // The account_id should be provided in the endpoint or can be extracted from metadata
        
        if (empty($config['endpoint'])) {
            // If no endpoint is provided, try to construct from account_id in metadata
            $accountId = $config['metadata']['account_id'] ?? null;
            if ($accountId) {
                $config['endpoint'] = 'https://' . $accountId . '.r2.cloudflarestorage.com';
            }
        }
        
        // Cloudflare R2 requires 'auto' as the region for the S3 API
        // but we store the actual region for user reference
        $config['region'] = $config['region'] ?? 'auto';
        
        // Cloudflare R2 works with path-style endpoints
        // Default to true for better compatibility
        if (!isset($config['use_path_style_endpoint'])) {
            $config['use_path_style_endpoint'] = true;
        }
        
        parent::__construct($config);
    }
}
