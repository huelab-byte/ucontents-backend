<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Drivers;

class DoS3StorageDriver extends S3StorageDriver
{
    public function __construct(array $config)
    {
        // DigitalOcean Spaces uses S3-compatible API
        // Default endpoint format: {region}.digitaloceanspaces.com
        if (empty($config['endpoint']) && !empty($config['region'])) {
            $config['endpoint'] = 'https://' . $config['region'] . '.digitaloceanspaces.com';
        } elseif (!empty($config['endpoint']) && !empty($config['region'])) {
            // Validate and fix endpoint format if user provided incorrect format
            $endpoint = $config['endpoint'];
            // If endpoint doesn't contain the region, fix it
            if (strpos($endpoint, $config['region']) === false && strpos($endpoint, 'digitaloceanspaces.com') !== false) {
                // Replace generic digitaloceanspaces.com with region-specific
                $config['endpoint'] = 'https://' . $config['region'] . '.digitaloceanspaces.com';
            }
        }
        
        // DigitalOcean Spaces typically works better with path-style endpoints disabled (virtual-hosted style)
        // But we'll respect the user's setting if they explicitly set it
        if (!isset($config['use_path_style_endpoint'])) {
            $config['use_path_style_endpoint'] = false;
        }
        
        parent::__construct($config);
    }
}
