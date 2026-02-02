<?php

namespace Modules\CustomerManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class CustomerManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CustomerManagementSeeder::class,
        ]);
    }
}
