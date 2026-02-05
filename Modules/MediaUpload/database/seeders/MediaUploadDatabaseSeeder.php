<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

class MediaUploadDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MediaUploadSettingsSeeder::class);

        $permissions = [
            'view_media_upload_folders' => 'View media upload folders',
            'manage_media_upload_folders' => 'Manage media upload folders',
            'upload_media' => 'Upload media',
            'manage_media_uploads' => 'Manage media uploads',
            'manage_caption_templates' => 'Manage caption templates',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $description) {
            $p = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'MediaUpload',
                ]
            );
            $permissionIds[] = $p->id;
        }

        $adminPermissions = Permission::whereIn('slug', array_keys($permissions))->pluck('id')->toArray();

        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $existing = $superAdmin->permissions()->pluck('permissions.id')->toArray();
            $superAdmin->permissions()->sync(array_unique(array_merge($existing, $permissionIds)));
        }

        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existing = $admin->permissions()->pluck('permissions.id')->toArray();
            $admin->permissions()->sync(array_unique(array_merge($existing, $adminPermissions)));
        }

        $customer = Role::where('slug', 'customer')->first();
        if ($customer) {
            $existing = $customer->permissions()->pluck('permissions.id')->toArray();
            $customer->permissions()->sync(array_unique(array_merge($existing, $adminPermissions)));
        }
    }
}
