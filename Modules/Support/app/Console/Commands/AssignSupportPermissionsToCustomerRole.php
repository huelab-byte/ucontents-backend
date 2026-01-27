<?php

declare(strict_types=1);

namespace Modules\Support\Console\Commands;

use Illuminate\Console\Command;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class AssignSupportPermissionsToCustomerRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:assign-customer-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign support ticket and notification permissions to customer role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Assigning support permissions to customer role...');

        // Find customer role (could be 'customer' or 'user' slug)
        $customerRole = Role::whereIn('slug', ['customer', 'user'])->first();

        if (!$customerRole) {
            $this->error('Customer role not found. Please create a customer role first.');
            return Command::FAILURE;
        }

        // Support ticket permissions
        $supportPermissions = [
            'view_own_tickets',
            'create_tickets',
            'reply_to_own_tickets',
        ];

        // Notification permissions
        $notificationPermissions = [
            'view_notifications',
        ];

        $allPermissions = array_merge($supportPermissions, $notificationPermissions);

        // Get or create permissions
        $permissionIds = [];
        foreach ($allPermissions as $permissionSlug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $permissionSlug],
                ['name' => ucwords(str_replace('_', ' ', $permissionSlug))]
            );
            $permissionIds[] = $permission->id;
        }

        // Attach permissions to customer role (without detaching existing ones)
        $existingPermissionIds = $customerRole->permissions()->pluck('permissions.id')->toArray();
        $newPermissionIds = array_diff($permissionIds, $existingPermissionIds);

        if (!empty($newPermissionIds)) {
            $customerRole->permissions()->attach($newPermissionIds);
            $this->info("✓ Assigned " . count($newPermissionIds) . " new permissions to customer role");
        } else {
            $this->info("✓ All permissions already assigned to customer role");
        }

        $this->info("\nAssigned permissions:");
        foreach ($allPermissions as $permissionSlug) {
            $hasPermission = $customerRole->hasPermission($permissionSlug);
            $status = $hasPermission ? '✓' : '✗';
            $this->line("  {$status} {$permissionSlug}");
        }

        $this->info("\n✓ Permissions assignment completed successfully!");

        return Command::SUCCESS;
    }
}
