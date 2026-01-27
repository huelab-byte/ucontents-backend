<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\User;
use Tests\TestCase;

class SocialConnectionProviderAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_providers_when_has_permission(): void
    {
        $permission = Permission::create([
            'name' => 'Manage Social Connection Providers',
            'slug' => 'manage_social_connection_providers',
            'description' => 'Full access to SocialConnection provider configuration',
            'module' => 'SocialConnection',
        ]);

        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Admin role',
            'hierarchy' => 10,
            'is_system' => false,
        ]);
        $role->permissions()->attach($permission->id);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => User::STATUS_ACTIVE,
            'is_system' => false,
        ]);
        $admin->roles()->attach($role->id);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/social-connection/providers');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        // Auto-seeded provider rows for UI convenience
        $this->assertDatabaseHas('social_provider_apps', ['provider' => 'meta']);
        $this->assertDatabaseHas('social_provider_apps', ['provider' => 'google']);
        $this->assertDatabaseHas('social_provider_apps', ['provider' => 'tiktok']);
    }

    public function test_customer_cannot_access_admin_provider_routes(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'status' => User::STATUS_ACTIVE,
            'is_system' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/social-connection/providers');

        $response->assertStatus(403);
    }
}

