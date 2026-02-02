<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class BulkPostingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_bulk_posting_campaigns' => 'View bulk posting campaigns',
            'manage_bulk_posting_campaigns' => 'Manage bulk posting campaigns (create, edit, delete)',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'BulkPosting',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existingPermissionIds = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $superAdmin->permissions()->sync($newPermissionIds);
        }

        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $admin->permissions()->sync($newPermissionIds);
        }

        $customer = Role::where('slug', 'customer')->first();
        if ($customer) {
            $existingPermissionIds = $customer->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $permissionIds));
            $customer->permissions()->sync($newPermissionIds);
        }

        $this->command->info('BulkPosting permissions seeded successfully.');
    }
}
