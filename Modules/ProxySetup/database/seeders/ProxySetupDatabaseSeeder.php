<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class ProxySetupDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view_proxies' => 'View proxy configurations',
            'manage_proxies' => 'Manage proxy configurations (create, edit, delete)',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'ProxySetup',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Super admin gets all permissions
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existingPermissionIds = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $superAdmin->permissions()->sync($newPermissionIds);
            $this->command->info('ProxySetup permissions assigned to Super Admin role.');
        }

        // Admin role gets all proxy permissions (so admins can test/demonstrate the feature)
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('ProxySetup permissions assigned to Admin role.');
        }

        $this->command->info('ProxySetup permissions seeded successfully.');
    }
}
