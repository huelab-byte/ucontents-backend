<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;

class ImageLibraryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_image' => 'Upload image',
            'bulk_upload_image' => 'Bulk upload image',
            'view_image' => 'View own image',
            'manage_image' => 'Edit/delete own image',
            'manage_image_folders' => 'Manage image folders',
            'view_all_image' => 'View all image (admin)',
            'delete_any_image' => 'Delete any image (admin)',
            'view_image_stats' => 'View image statistics (admin)',
            'view_image_library' => 'View image library',
            'manage_image_library' => 'Manage image library',
        ];

        foreach ($permissions as $slug => $description) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'ImageLibrary',
                ]
            );
        }
    }
}
