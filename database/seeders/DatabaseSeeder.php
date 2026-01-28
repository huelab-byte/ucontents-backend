<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\UserManagement\Database\Seeders\UserManagementSeeder;

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

        // Seed Support module (creates support permissions)
        $this->call(\Modules\Support\Database\Seeders\SupportDatabaseSeeder::class);
    }
}
