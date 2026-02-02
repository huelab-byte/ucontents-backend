<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class VideoOverlayDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_video_overlay' => 'Upload video overlay',
            'view_video_overlay' => 'View own video overlay',
            'manage_video_overlay' => 'Edit/delete own video overlay',
            'manage_video_overlay_folders' => 'Manage video overlay folders',
            'view_all_video_overlay' => 'View all video overlay (admin)',
            'delete_any_video_overlay' => 'Delete any video overlay (admin)',
            'view_video_overlay_stats' => 'View video overlay statistics (admin)',
            'view_video_overlay_library' => 'View video overlay library',
            'manage_video_overlay_library' => 'Manage video overlay library',
            'use_video_overlay' => 'Browse and use shared video overlays (read-only)',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'VideoOverlay',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Assign admin permissions to super_admin and admin roles
        $adminPermissions = Permission::whereIn('slug', [
            'view_video_overlay_library',
            'manage_video_overlay_library',
            'view_all_video_overlay',
            'view_video_overlay_stats',
            'delete_any_video_overlay',
            'manage_video_overlay_folders', // Required for listing/accessing folders
        ])->pluck('id')->toArray();

        // Super admin gets all permissions
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existingPermissionIds = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $superAdmin->permissions()->sync($newPermissionIds);
            $this->command->info('VideoOverlay permissions assigned to Super Admin role.');
        }

        // Admin role gets admin-level permissions
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $adminPermissions));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('VideoOverlay permissions assigned to Admin role.');
        }
    }
}
