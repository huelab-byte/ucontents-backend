<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Drivers;

class ContaboS3StorageDriver extends S3StorageDriver
{
    public function __construct(array $config)
    {
        // Contabo Object Storage uses S3-compatible API
        // Default endpoint format: {region}.contaboserver.net
        if (empty($config['endpoint']) && !empty($config['region'])) {
            $config['endpoint'] = 'https://' . $config['region'] . '.contaboserver.net';
        }
        
        // Contabo typically requires path-style endpoints
        $config['use_path_style_endpoint'] = $config['use_path_style_endpoint'] ?? true;
        
        parent::__construct($config);
    }
}
