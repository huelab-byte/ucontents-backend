<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class PlanManagementDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanManagementPermissionsSeeder::class,
        ]);
    }
}
