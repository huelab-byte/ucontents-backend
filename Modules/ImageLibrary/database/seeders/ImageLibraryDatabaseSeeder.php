<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class ImageLibraryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_image' => 'Upload image',
            'bulk_upload_image' => 'Bulk upload image',
            'view_image' => 'View own image',
            'manage_image' => 'Edit/delete own image',
            'manage_image_folders' => 'Manage image folders',
            'view_all_image' => 'View all image (admin)',
            'delete_any_image' => 'Delete any image (admin)',
            'view_image_stats' => 'View image statistics (admin)',
            'view_image_library' => 'View image library',
            'manage_image_library' => 'Manage image library',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'ImageLibrary',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Assign admin permissions to super_admin and admin roles
        $adminPermissions = Permission::whereIn('slug', [
            'view_image_library',
            'manage_image_library',
            'view_all_image',
            'view_image_stats',
            'delete_any_image',
        ])->pluck('id')->toArray();

        // Super admin gets all permissions
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existingPermissionIds = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $superAdmin->permissions()->sync($newPermissionIds);
            $this->command->info('ImageLibrary permissions assigned to Super Admin role.');
        }

        // Admin role gets admin-level permissions
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $adminPermissions));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('ImageLibrary permissions assigned to Admin role.');
        }
    }
}
