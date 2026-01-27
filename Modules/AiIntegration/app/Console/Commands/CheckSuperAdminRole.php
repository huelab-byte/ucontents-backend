<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Console\Commands;

use Illuminate\Console\Command;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\User;

/**
 * Check and fix super admin role assignment
 */
class CheckSuperAdminRole extends Command
{
    protected $signature = 'ai-integration:check-super-admin {email?}';

    protected $description = 'Check if a user has super_admin role and assign it if missing';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Enter user email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found!");
            return Command::FAILURE;
        }
        
        $user->load('roles');
        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Current roles: " . $user->roles->pluck('slug')->implode(', '));
        
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        
        if (!$superAdminRole) {
            $this->error('Super admin role not found!');
            return Command::FAILURE;
        }
        
        if ($user->hasRole('super_admin')) {
            $this->info('✓ User already has super_admin role');
        } else {
            $this->warn('✗ User does NOT have super_admin role');
            
            if ($this->confirm('Assign super_admin role to this user?', true)) {
                $user->roles()->syncWithoutDetaching([$superAdminRole->id]);
                $this->info('✓ Super admin role assigned successfully!');
            }
        }
        
        return Command::SUCCESS;
    }
}
