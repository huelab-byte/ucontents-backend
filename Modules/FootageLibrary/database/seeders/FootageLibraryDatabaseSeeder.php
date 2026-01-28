<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class FootageLibraryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_footage' => 'Upload footage',
            'bulk_upload_footage' => 'Bulk upload footage',
            'view_footage' => 'View own footage',
            'manage_footage' => 'Edit/delete own footage',
            'search_footage' => 'Use AI search for footage',
            'manage_footage_folders' => 'Manage footage folders',
            'view_all_footage' => 'View all footage (admin)',
            'delete_any_footage' => 'Delete any footage (admin)',
            'view_footage_stats' => 'View footage statistics (admin)',
            'view_footage_library' => 'View footage library',
            'manage_footage_library' => 'Manage footage library',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'FootageLibrary',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Assign admin permissions to super_admin and admin roles
        $adminPermissions = Permission::whereIn('slug', [
            'view_footage_library',
            'manage_footage_library',
            'view_all_footage',
            'view_footage_stats',
            'delete_any_footage',
        ])->pluck('id')->toArray();

        // Super admin gets all permissions
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existingPermissionIds = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $superAdmin->permissions()->sync($newPermissionIds);
            $this->command->info('FootageLibrary permissions assigned to Super Admin role.');
        }

        // Admin role gets admin-level permissions
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $adminPermissions));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('FootageLibrary permissions assigned to Admin role.');
        }
    }
}
