<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Actions;

use Modules\StorageManagement\Models\StorageSetting;

/**
 * Action to delete a storage setting
 */
class DeleteStorageSettingAction
{
    /**
     * @throws \Exception
     */
    public function execute(StorageSetting $storageSetting): bool
    {
        if ($storageSetting->is_active) {
            throw new \Exception('Cannot delete the active storage configuration. Please activate another storage first.');
        }

        return $storageSetting->delete();
    }
}
