<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class AiIntegrationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'manage_ai_integration' => 'Manage AI Integration settings',
            'manage_ai_providers' => 'Manage AI Providers',
            'manage_ai_api_keys' => 'Manage AI API Keys',
            'view_ai_usage' => 'View AI Usage Statistics',
            'manage_prompt_templates' => 'Manage Prompt Templates',
            'use_prompt_templates' => 'Use Prompt Templates',
            'call_ai_models' => 'Call AI Models via API',
            'use_ai_chat' => 'Use AI Chat interface',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'AiIntegration',
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // 1. Assign Admin permissions
        $adminPermissions = Permission::whereIn('slug', [
            'manage_ai_integration',
            'manage_ai_providers',
            'manage_ai_api_keys',
            'view_ai_usage',
            'manage_prompt_templates',
            // Admin also gets user features usually, or rely on explicit assignment
            'use_prompt_templates',
            'call_ai_models',
            'use_ai_chat',
        ])->pluck('id')->toArray();

        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching($adminPermissions);
            $this->command->info('AiIntegration permissions assigned to Admin role.');
        }

        // 2. Assign Customer permissions
        $customerPermissions = Permission::whereIn('slug', [
            'use_prompt_templates',
            'call_ai_models',
            'use_ai_chat',
        ])->pluck('id')->toArray();

        $customer = Role::where('slug', 'customer')->first();
        if ($customer) {
            $customer->permissions()->syncWithoutDetaching($customerPermissions);
            $this->command->info('AiIntegration permissions assigned to Customer role.');
        }

        // 3. Assign Manager permissions (same as customer + maybe more?)
        $manager = Role::where('slug', 'manager')->first();
        if ($manager) {
            $manager->permissions()->syncWithoutDetaching($customerPermissions);
            $this->command->info('AiIntegration permissions assigned to Manager role.');
        }

        // 4. Assign to Super Admin (all)
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
            $this->command->info('AiIntegration permissions assigned to Super Admin role.');
        }
    }
}
