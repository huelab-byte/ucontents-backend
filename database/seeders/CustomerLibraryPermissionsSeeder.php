<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Creates use_* permissions (if missing) and assigns them to the customer role.
 * Run: php artisan db:seed --class=CustomerLibraryPermissionsSeeder
 */
class CustomerLibraryPermissionsSeeder extends Seeder
{
    private const USE_PERMISSIONS = [
        'use_audio_library' => ['module' => 'AudioLibrary', 'description' => 'Browse and use shared audio library (read-only)'],
        'use_image_library' => ['module' => 'ImageLibrary', 'description' => 'Browse and use shared image library (read-only)'],
        'use_footage_library' => ['module' => 'FootageLibrary', 'description' => 'Browse and use shared footage library (read-only)'],
        'use_bgm_library' => ['module' => 'BgmLibrary', 'description' => 'Browse and use shared BGM library (read-only)'],
        'use_video_overlay' => ['module' => 'VideoOverlay', 'description' => 'Browse and use shared video overlays (read-only)'],
        'use_image_overlay' => ['module' => 'ImageOverlay', 'description' => 'Browse and use shared image overlays (read-only)'],
    ];

    public function run(): void
    {
        $customer = Role::where('slug', 'customer')->first();
        if (!$customer) {
            $this->command->warn('Customer role not found. Run UserManagementSeeder first.');
            return;
        }

        $usePermissionIds = [];
        foreach (self::USE_PERMISSIONS as $slug => $config) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $config['description'],
                    'module' => $config['module'],
                ]
            );
            $usePermissionIds[] = $permission->id;
        }

        $existingPermissionIds = $customer->permissions()->pluck('permissions.id')->toArray();
        $newPermissionIds = array_unique(array_merge($existingPermissionIds, $usePermissionIds));
        $customer->permissions()->sync($newPermissionIds);
        $this->command->info('Customer role assigned library/overlay browse permissions: ' . implode(', ', array_keys(self::USE_PERMISSIONS)));
    }
}
