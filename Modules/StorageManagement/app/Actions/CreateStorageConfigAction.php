<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Actions;

use Modules\StorageManagement\DTOs\StorageConfigDTO;
use Modules\StorageManagement\Models\StorageSetting;

class CreateStorageConfigAction
{
    public function execute(StorageConfigDTO $dto): StorageSetting
    {
        // Create new storage configuration as inactive
        // User will activate it manually using the activate button
        return StorageSetting::create([
            'driver' => $dto->driver,
            'is_active' => false, // New configs are not active by default
            'key' => $dto->key,
            'secret' => $dto->secret,
            'region' => $dto->region,
            'bucket' => $dto->bucket,
            'endpoint' => $dto->endpoint,
            'url' => $dto->url,
            'use_path_style_endpoint' => $dto->usePathStyleEndpoint,
            'root_path' => $dto->rootPath,
            'metadata' => $dto->metadata,
        ]);
    }
}
