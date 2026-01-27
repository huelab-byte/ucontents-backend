<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;

class AudioLibraryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'upload_audio' => 'Upload audio',
            'bulk_upload_audio' => 'Bulk upload audio',
            'view_audio' => 'View own audio',
            'manage_audio' => 'Edit/delete own audio',
            'manage_audio_folders' => 'Manage audio folders',
            'view_all_audio' => 'View all audio (admin)',
            'delete_any_audio' => 'Delete any audio (admin)',
            'view_audio_stats' => 'View audio statistics (admin)',
            'view_audio_library' => 'View audio library',
            'manage_audio_library' => 'Manage audio library',
        ];

        foreach ($permissions as $slug => $description) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'AudioLibrary',
                ]
            );
        }
    }
}
