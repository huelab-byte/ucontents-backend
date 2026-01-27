<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class EmailManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DefaultEmailTemplatesSeeder::class,
        ]);
    }
}
