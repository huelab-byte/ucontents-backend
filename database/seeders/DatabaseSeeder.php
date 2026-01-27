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
    }
}
