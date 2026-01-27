<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Actions;

use Modules\StorageManagement\DTOs\StorageConfigDTO;
use Modules\StorageManagement\Models\StorageSetting;

class UpdateStorageConfigAction
{
    public function execute(StorageSetting $setting, StorageConfigDTO $dto): StorageSetting
    {
        $updateData = [
            'driver' => $dto->driver,
            'use_path_style_endpoint' => $dto->usePathStyleEndpoint,
        ];

        // Update fields - only update secret if a new value is provided (not null, not empty)
        // This allows updating other fields without needing to provide the secret again
        if ($dto->key !== null) {
            $updateData['key'] = $dto->key;
        }
        if ($dto->secret !== null && $dto->secret !== '') {
            $updateData['secret'] = $dto->secret;
        }
        if ($dto->region !== null) {
            $updateData['region'] = $dto->region;
        }
        if ($dto->bucket !== null) {
            $updateData['bucket'] = $dto->bucket;
        }
        if ($dto->endpoint !== null) {
            $updateData['endpoint'] = $dto->endpoint;
        }
        if ($dto->url !== null) {
            $updateData['url'] = $dto->url;
        }
        if ($dto->rootPath !== null) {
            $updateData['root_path'] = $dto->rootPath;
        }
        if ($dto->metadata !== null) {
            $updateData['metadata'] = $dto->metadata;
        }

        $setting->update($updateData);

        return $setting->fresh();
    }
}
