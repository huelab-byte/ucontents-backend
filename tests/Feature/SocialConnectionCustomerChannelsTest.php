<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\UserManagement\Models\User;
use Tests\TestCase;

class SocialConnectionCustomerChannelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_without_state_redirects_with_error(): void
    {
        SocialProviderApp::create([
            'provider' => 'google',
            'enabled' => true,
            'client_id' => 'x',
            'client_secret' => 'y',
            'scopes' => [],
            'extra' => [],
        ]);

        $response = $this->get('/api/v1/customer/social-connection/google/callback');
        $response->assertRedirect();
        $this->assertStringContainsString('status=error', $response->headers->get('Location'));
    }

    public function test_channels_endpoint_scoped_to_authenticated_user(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer2@example.com',
            'password' => bcrypt('password'),
            'status' => User::STATUS_ACTIVE,
            'is_system' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customer/social-connection/channels');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'pagination']);
    }
}

