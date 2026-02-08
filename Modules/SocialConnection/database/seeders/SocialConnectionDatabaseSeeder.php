<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class SocialConnectionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionsPath = base_path('Modules/Core/config/permissions.php');
        $allPermissions = file_exists($permissionsPath) ? require $permissionsPath : [];

        $permissions = [
            'manage_social_connection_providers' => $allPermissions['manage_social_connection_providers'] ?? 'Manage SocialConnection provider apps',
            'manage_social_connection_groups' => $allPermissions['manage_social_connection_groups'] ?? 'Manage connection groups and move connections to groups (customer)',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'SocialConnection',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Super admin gets all permissions (via syncSuperAdminPermissions in DatabaseSeeder)

        // Admin gets provider management
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $providerPermissionId = Permission::where('slug', 'manage_social_connection_providers')->pluck('id')->toArray();
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $providerPermissionId));
            $admin->permissions()->sync($newPermissionIds);
        }

        // Customer gets group management (create groups, move connections)
        $customer = Role::where('slug', 'customer')->first();
        if ($customer) {
            $groupPermissionId = Permission::where('slug', 'manage_social_connection_groups')->pluck('id')->toArray();
            $existingPermissionIds = $customer->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $groupPermissionId));
            $customer->permissions()->sync($newPermissionIds);
            $this->command?->info('SocialConnection: manage_social_connection_groups assigned to customer role.');
        }
    }
}
