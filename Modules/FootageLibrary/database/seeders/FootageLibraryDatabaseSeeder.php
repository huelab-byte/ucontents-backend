<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Illuminate\Support\Facades\DB;

class FootageLibraryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_footage' => 'Upload footage',
            'bulk_upload_footage' => 'Bulk upload footage',
            'view_footage' => 'View own footage',
            'manage_footage' => 'Edit/delete own footage',
            'search_footage' => 'Use AI search for footage',
            'manage_footage_folders' => 'Manage footage folders',
            'view_all_footage' => 'View all footage (admin)',
            'delete_any_footage' => 'Delete any footage (admin)',
            'view_footage_stats' => 'View footage statistics (admin)',
        ];

        foreach ($permissions as $slug => $description) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'FootageLibrary',
                ]
            );
        }
    }
}
