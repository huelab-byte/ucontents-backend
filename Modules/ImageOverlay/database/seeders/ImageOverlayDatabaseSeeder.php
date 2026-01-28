<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class ImageOverlayDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_image_overlay' => 'Upload image overlay',
            'bulk_upload_image_overlay' => 'Bulk upload image overlay',
            'view_image_overlay' => 'View own image overlay',
            'manage_image_overlay' => 'Edit/delete own image overlay',
            'manage_image_overlay_folders' => 'Manage image overlay folders',
            'view_all_image_overlay' => 'View all image overlay (admin)',
            'delete_any_image_overlay' => 'Delete any image overlay (admin)',
            'view_image_overlay_stats' => 'View image overlay statistics (admin)',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'ImageOverlay',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Assign admin permissions to super_admin and admin roles
        $adminPermissions = Permission::whereIn('slug', [
            'view_all_image_overlay',
            'view_image_overlay_stats',
            'delete_any_image_overlay',
        ])->pluck('id')->toArray();

        // Super admin gets all permissions
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existingPermissionIds = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $superAdmin->permissions()->sync($newPermissionIds);
            $this->command->info('ImageOverlay permissions assigned to Super Admin role.');
        }

        // Admin role gets admin-level permissions
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $adminPermissions));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('ImageOverlay permissions assigned to Admin role.');
        }
    }
}
