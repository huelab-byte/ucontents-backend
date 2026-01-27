<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Drivers;

class BackblazeB2StorageDriver extends S3StorageDriver
{
    public function __construct(array $config)
    {
        // Backblaze B2 uses S3-compatible API
        // Endpoint format: https://s3.<region>.backblazeb2.com
        // Common regions: us-west-000, us-west-001, us-west-002, us-west-004, eu-central-003
        
        if (empty($config['endpoint']) && !empty($config['region'])) {
            $config['endpoint'] = 'https://s3.' . $config['region'] . '.backblazeb2.com';
        }
        
        // Backblaze B2 S3-compatible API works best with path-style endpoints
        if (!isset($config['use_path_style_endpoint'])) {
            $config['use_path_style_endpoint'] = true;
        }
        
        parent::__construct($config);
    }
}
