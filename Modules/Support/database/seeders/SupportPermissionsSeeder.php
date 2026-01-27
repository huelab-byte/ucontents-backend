<?php

declare(strict_types=1);

namespace Modules\Support\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Seeder for Support module permissions
 * Creates support permissions and assigns them to super_admin and admin roles
 */
class SupportPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPermissions();
        $this->assignPermissionsToRoles();
    }

    /**
     * Seed support-related permissions
     */
    private function seedPermissions(): void
    {
        $permissions = [
            // Customer permissions
            'view_own_tickets' => 'View own support tickets',
            'create_tickets' => 'Create support tickets',
            'reply_to_own_tickets' => 'Reply to own tickets',
            'upload_files' => 'Upload files to storage',
            
            // Admin permissions
            'view_all_tickets' => 'View all support tickets',
            'manage_tickets' => 'Manage support tickets',
            'assign_tickets' => 'Assign support tickets',
        ];

        foreach ($permissions as $slug => $description) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'Support',
                ]
            );
        }

        $this->command->info('Support permissions seeded successfully.');
    }

    /**
     * Assign support permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        // Get all support permissions
        $adminPermissions = Permission::whereIn('slug', [
            'view_all_tickets',
            'manage_tickets',
            'assign_tickets',
        ])->pluck('id')->toArray();

        $customerPermissions = Permission::whereIn('slug', [
            'view_own_tickets',
            'create_tickets',
            'reply_to_own_tickets',
            'view_notifications',
            'upload_files',
        ])->pluck('id')->toArray();

        // Assign to super_admin (gets all permissions)
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            // Get all permissions and sync
            $allPermissionIds = Permission::pluck('id')->toArray();
            $superAdmin->permissions()->sync($allPermissionIds);
            $this->command->info('All permissions assigned to Super Admin role.');
        }

        // Assign admin permissions to admin role
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            // Merge existing permissions with new support permissions
            $existingPermissionIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $adminPermissions));
            $admin->permissions()->sync($newPermissionIds);
            $this->command->info('Support permissions assigned to Admin role.');
        }

        // Assign customer permissions to customer role
        $customer = Role::where('slug', 'customer')->first();
        if ($customer) {
            // Merge existing permissions with new support permissions
            $existingPermissionIds = $customer->permissions()->pluck('permissions.id')->toArray();
            $newPermissionIds = array_unique(array_merge($existingPermissionIds, $customerPermissions));
            $customer->permissions()->sync($newPermissionIds);
            $this->command->info('Support permissions assigned to Customer role.');
        }
    }
}
