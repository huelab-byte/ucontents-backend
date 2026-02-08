<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\UserManagement\Database\Seeders\UserManagementSeeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed UserManagement module (users, roles, permissions)
        $this->call(UserManagementSeeder::class);

        // Seed EmailManagement module (default email templates)
        $this->call(\Modules\EmailManagement\Database\Seeders\EmailManagementDatabaseSeeder::class);

        // Seed Authentication module (default authentication settings)
        $this->call(\Modules\Authentication\Database\Seeders\AuthenticationSettingsSeeder::class);

        // Seed library modules (creates permissions for each library)
        $this->call(\Modules\AudioLibrary\Database\Seeders\AudioLibraryDatabaseSeeder::class);
        $this->call(\Modules\ImageLibrary\Database\Seeders\ImageLibraryDatabaseSeeder::class);
        $this->call(\Modules\FootageLibrary\Database\Seeders\FootageLibraryDatabaseSeeder::class);
        $this->call(\Modules\BgmLibrary\Database\Seeders\BgmLibraryDatabaseSeeder::class);
        $this->call(\Modules\VideoOverlay\Database\Seeders\VideoOverlayDatabaseSeeder::class);
        $this->call(\Modules\ImageOverlay\Database\Seeders\ImageOverlayDatabaseSeeder::class);
        $this->call(\Modules\MediaUpload\Database\Seeders\MediaUploadDatabaseSeeder::class);

        // Seed Support module (creates support permissions)
        $this->call(\Modules\Support\Database\Seeders\SupportDatabaseSeeder::class);

        // Seed PlanManagement module (creates plan permissions)
        $this->call(\Modules\PlanManagement\Database\Seeders\PlanManagementDatabaseSeeder::class);

        // Seed ProxySetup module (creates proxy permissions)
        $this->call(\Modules\ProxySetup\Database\Seeders\ProxySetupDatabaseSeeder::class);

        // Seed BulkPosting module (creates bulk posting permissions)
        $this->call(\Modules\BulkPosting\Database\Seeders\BulkPostingDatabaseSeeder::class);

        // Seed CustomerManagement module (creates customer management permissions)
        $this->call(\Modules\CustomerManagement\Database\Seeders\CustomerManagementDatabaseSeeder::class);

        // Seed AiIntegration module (providers, permissions)
        $this->call(\Modules\AiIntegration\Database\Seeders\AiIntegrationDatabaseSeeder::class);

        // Seed GeneralSettings module
        $this->call(\Modules\GeneralSettings\Database\Seeders\GeneralSettingsDatabaseSeeder::class);

        // Seed SocialConnection module (providers + groups permissions)
        $this->call(\Modules\SocialConnection\Database\Seeders\SocialConnectionDatabaseSeeder::class);

        // Assign default "browse shared library/overlay" permissions to customer role
        $this->syncCustomerLibraryPermissions();

        // Ensure super_admin has ALL permissions (system user - full access)
        $this->syncSuperAdminPermissions();
    }

    /**
     * Assign default use_* permissions to customer role so customers can browse shared library/overlay (read-only).
     */
    private function syncCustomerLibraryPermissions(): void
    {
        $customer = Role::where('slug', 'customer')->first();
        if (!$customer) {
            return;
        }

        $usePermissions = [
            'use_audio_library',
            'use_image_library',
            'use_footage_library',
            'use_bgm_library',
            'use_video_overlay',
            'use_image_overlay',
        ];

        $usePermissionIds = Permission::whereIn('slug', $usePermissions)->pluck('id')->toArray();
        if (empty($usePermissionIds)) {
            $this->command->warn('No use_* permissions found. Run library/overlay module seeders first.');
            return;
        }

        $existingPermissionIds = $customer->permissions()->pluck('permissions.id')->toArray();
        $newPermissionIds = array_unique(array_merge($existingPermissionIds, $usePermissionIds));
        $customer->permissions()->sync($newPermissionIds);
        $this->command->info('Customer role assigned default library/overlay browse permissions (use_*).');
    }

    /**
     * Sync all permissions to super_admin role so the system user always has full access.
     */
    private function syncSuperAdminPermissions(): void
    {
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if (!$superAdmin) {
            return;
        }

        $allPermissionIds = Permission::pluck('id')->toArray();
        $superAdmin->permissions()->sync($allPermissionIds);
        $this->command->info('Super Admin role synced with all ' . count($allPermissionIds) . ' permissions.');
    }
}
