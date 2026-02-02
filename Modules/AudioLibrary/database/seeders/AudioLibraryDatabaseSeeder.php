<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class AudioLibraryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_audio' => 'Upload audio',
            'bulk_upload_audio' => 'Bulk upload audio',
            'view_audio' => 'View own audio',
            'manage_audio' => 'Edit/delete own audio',
            'manage_audio_folders' => 'Manage audio folders',
            'view_all_audio' => 'View all audio (admin)',
            'delete_any_audio' => 'Delete any audio (admin)',
            'view_audio_stats' => 'View audio statistics (admin)',
            'view_audio_library' => 'View audio library',
            'manage_audio_library' => 'Manage audio library',
            'use_audio_library' => 'Browse and use shared audio library (read-only)',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'AudioLibrary',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Assign admin permissions to super_admin and admin roles
        $adminPermissions = Permission::whereIn('slug', [
            'view_audio_library',
            'manage_audio_library',
            'view_all_audio',
            'view_audio_stats',
            'delete_any_audio',
            'manage_audio_folders', // Required for listing/accessing folders
        ])->pluck('id')->toArray();

        // Super admin gets all permissions
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existingPermissionIds = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $superAdmin->permissions()->sync($newPermissionIds);
            $this->command->info('AudioLibrary permissions assigned to Super Admin role.');
        }

        // Admin role gets admin-level permissions
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $adminPermissions));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('AudioLibrary permissions assigned to Admin role.');
        }
    }
}
