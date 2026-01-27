<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Policies;

use Modules\StorageManagement\Models\StorageSetting;
use Modules\UserManagement\Models\User;

class StorageSettingPolicy
{
    /**
     * Determine if the user can view any storage settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_storage_config') || $user->hasPermission('manage_storage_config');
    }

    /**
     * Determine if the user can view the storage setting.
     */
    public function view(User $user, StorageSetting $storageSetting): bool
    {
        return $user->hasPermission('view_storage_config') || $user->hasPermission('manage_storage_config');
    }

    /**
     * Determine if the user can create storage settings.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_storage_config');
    }

    /**
     * Determine if the user can update the storage setting.
     */
    public function update(User $user, StorageSetting $storageSetting): bool
    {
        return $user->hasPermission('manage_storage_config');
    }

    /**
     * Determine if the user can delete the storage setting.
     */
    public function delete(User $user, StorageSetting $storageSetting): bool
    {
        return $user->hasPermission('manage_storage_config');
    }
}
