<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Modules\Authentication\Models\SocialAuthProvider;
use Modules\Authentication\Services\AuthService;
use Modules\UserManagement\Models\User;

/**
 * Action to handle social authentication
 */
class SocialAuthAction
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    /**
     * Handle social authentication callback
     * Supports both login and registration - if user exists, login; if not, create account and login
     */
    public function execute(string $provider, SocialiteUser $socialiteUser): array
    {
        return DB::transaction(function () use ($provider, $socialiteUser) {
            $providerId = $socialiteUser->getId();
            $email = $socialiteUser->getEmail();
            
            if (!$email) {
                throw new \Exception('Email address is required for social authentication');
            }

            // Normalize email to lowercase to avoid duplicate entries
            $email = strtolower(trim($email));

            // Check if social auth provider already exists (by provider + provider_id)
            $socialAuth = SocialAuthProvider::where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();

            if ($socialAuth) {
                // User already linked via this provider - login
                $user = $socialAuth->user;
                
                // Update provider data
                $socialAuth->update([
                    'email' => $email,
                    'provider_data' => [
                        'name' => $socialiteUser->getName(),
                        'avatar' => $socialiteUser->getAvatar(),
                        'raw' => $socialiteUser->getRaw(),
                    ],
                ]);
            } else {
                // Check if user with this email already exists (to avoid duplicate)
                $user = User::where('email', $email)->first();

                if ($user) {
                    // User exists with this email - link the social provider to existing account
                    // Check if this provider is already linked to another account
                    $existingLink = SocialAuthProvider::where('provider', $provider)
                        ->where('provider_id', $providerId)
                        ->where('user_id', '!=', $user->id)
                        ->first();
                    
                    if ($existingLink) {
                        throw new \Exception('This social account is already linked to another user');
                    }

                    // Link the social provider to existing user
                    SocialAuthProvider::create([
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'provider_id' => $providerId,
                        'email' => $email,
                        'provider_data' => [
                            'name' => $socialiteUser->getName(),
                            'avatar' => $socialiteUser->getAvatar(),
                            'raw' => $socialiteUser->getRaw(),
                        ],
                    ]);
                } else {
                    // New user - create account and login
                    // Double-check email doesn't exist (race condition protection)
                    $existingUser = User::where('email', $email)->lockForUpdate()->first();
                    
                    if ($existingUser) {
                        // User was created between our check and now - link provider instead
                        SocialAuthProvider::create([
                            'user_id' => $existingUser->id,
                            'provider' => $provider,
                            'provider_id' => $providerId,
                            'email' => $email,
                            'provider_data' => [
                                'name' => $socialiteUser->getName(),
                                'avatar' => $socialiteUser->getAvatar(),
                                'raw' => $socialiteUser->getRaw(),
                            ],
                        ]);
                        $user = $existingUser;
                    } else {
                        // Create new user account
                        $user = User::create([
                            'name' => $socialiteUser->getName() ?? 'User',
                            'email' => $email,
                            'password' => bcrypt(str()->random(32)), // Random password since they use social auth
                            'email_verified_at' => now(), // Social providers verify email
                        ]);

                        // Assign default customer role
                        $user->assignRole('customer');

                        // Create social auth provider link
                        SocialAuthProvider::create([
                            'user_id' => $user->id,
                            'provider' => $provider,
                            'provider_id' => $providerId,
                            'email' => $email,
                            'provider_data' => [
                                'name' => $socialiteUser->getName(),
                                'avatar' => $socialiteUser->getAvatar(),
                                'raw' => $socialiteUser->getRaw(),
                            ],
                        ]);
                    }
                }
            }

            // Update last login timestamp
            $user->update(['last_login_at' => now()]);

            // Create authentication token
            $token = $this->authService->createToken($user, 'social-auth');

            return [
                'user' => $user->load('roles.permissions'),
                'token' => $token,
            ];
        });
    }
}
