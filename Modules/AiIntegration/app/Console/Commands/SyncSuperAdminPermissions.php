<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Console\Commands;

use Illuminate\Console\Command;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Sync all permissions to super_admin role
 */
class SyncSuperAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-integration:sync-super-admin-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all permissions (including AI Integration) to super_admin role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $superAdminRole = Role::where('slug', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->error('Super admin role not found!');
            return Command::FAILURE;
        }

        // Get all permissions
        $allPermissions = Permission::pluck('id')->toArray();
        
        // Sync all permissions to super_admin
        $superAdminRole->permissions()->sync($allPermissions);

        $this->info("Successfully synced " . count($allPermissions) . " permissions to super_admin role.");
        
        return Command::SUCCESS;
    }
}
