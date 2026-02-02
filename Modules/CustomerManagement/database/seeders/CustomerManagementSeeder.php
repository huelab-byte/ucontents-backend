<?php

declare(strict_types=1);

namespace Modules\CustomerManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Seeder for CustomerManagement module permissions.
 * Creates view_customers and manage_customers; assigns to admin role.
 * super_admin gets all permissions via DatabaseSeeder::syncSuperAdminPermissions().
 */
class CustomerManagementSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->assignPermissionsToAdmin();
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'view_customers' => 'View customers list and profiles',
            'manage_customers' => 'Manage customers (view, assign plan, etc.)',
        ];

        foreach ($permissions as $slug => $description) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'CustomerManagement',
                ]
            );
        }
    }

    private function assignPermissionsToAdmin(): void
    {
        $permissionIds = Permission::whereIn('slug', [
            'view_customers',
            'manage_customers',
        ])->pluck('id')->toArray();

        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $admin->permissions()->sync(array_unique(array_merge($existingIds, $permissionIds)));
        }
    }
}
