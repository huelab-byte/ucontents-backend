<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class BgmLibraryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_bgm' => 'Upload BGM',
            'bulk_upload_bgm' => 'Bulk upload BGM',
            'view_bgm' => 'View own BGM',
            'manage_bgm' => 'Edit/delete own BGM',
            'manage_bgm_folders' => 'Manage BGM folders',
            'view_all_bgm' => 'View all BGM (admin)',
            'delete_any_bgm' => 'Delete any BGM (admin)',
            'view_bgm_stats' => 'View BGM statistics (admin)',
            'use_bgm_library' => 'Browse and use shared BGM library (read-only)',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'BgmLibrary',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Assign admin permissions to super_admin and admin roles
        $adminPermissions = Permission::whereIn('slug', [
            'view_all_bgm',
            'view_bgm_stats',
            'delete_any_bgm',
            'manage_bgm_folders', // Required for listing/accessing folders
        ])->pluck('id')->toArray();

        // Super admin gets all permissions
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existingPermissionIds = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $superAdmin->permissions()->sync($newPermissionIds);
            $this->command->info('BgmLibrary permissions assigned to Super Admin role.');
        }

        // Admin role gets admin-level permissions
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $adminPermissions));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('BgmLibrary permissions assigned to Admin role.');
        }
    }
}
