<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Console\Commands;

use Illuminate\Console\Command;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class SeedSocialConnectionPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'social-connection:seed-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed SocialConnection permissions and assign to super_admin by default';

    public function handle(): int
    {
        $permissionsPath = base_path('Modules/Core/config/permissions.php');
        $allPermissions = file_exists($permissionsPath) ? require $permissionsPath : [];

        $slug = 'manage_social_connection_providers';
        $description = $allPermissions[$slug] ?? 'Manage SocialConnection provider apps';

        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => 'Manage Social Connection Providers',
                'description' => $description,
                'module' => 'SocialConnection',
            ]
        );

        if (!$permission->wasRecentlyCreated && $permission->description !== $description) {
            $permission->update(['description' => $description]);
        }

        $superAdmin = Role::where('slug', 'super_admin')->first();
        if (!$superAdmin) {
            $this->warn('super_admin role not found. Permission was created but not assigned.');
            return Command::SUCCESS;
        }

        if (!$superAdmin->permissions()->where('slug', $slug)->exists()) {
            $superAdmin->permissions()->attach($permission->id);
        }

        $this->info("Seeded permission '{$slug}' and ensured it's assigned to super_admin.");

        return Command::SUCCESS;
    }
}

