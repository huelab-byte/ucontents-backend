<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class PlanManagementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->assignPermissionsToRoles();
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'view_plans' => 'View plans',
            'manage_plans' => 'Manage plans (create, update, delete)',
            'view_own_subscription' => 'View own subscription',
            'subscribe_to_plan' => 'Subscribe to a plan',
        ];

        foreach ($permissions as $slug => $description) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'PlanManagement',
                ]
            );
        }

        $this->command->info('PlanManagement permissions seeded successfully.');
    }

    private function assignPermissionsToRoles(): void
    {
        $adminPermissions = Permission::whereIn('slug', [
            'view_plans',
            'manage_plans',
        ])->pluck('id')->toArray();

        $customerPermissions = Permission::whereIn('slug', [
            'view_own_subscription',
            'subscribe_to_plan',
        ])->pluck('id')->toArray();

        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $adminPermissions));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('PlanManagement admin permissions assigned to Admin role.');
        }

        $customer = Role::where('slug', 'customer')->first();
        if ($customer) {
            $existingPermissionIds = $customer->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $customerPermissions));
            $customer->permissions()->sync($newPermissionIds);
            $this->command->info('PlanManagement customer permissions assigned to Customer role.');
        }
    }
}
