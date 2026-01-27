<?php

declare(strict_types=1);

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Authentication\Services\AuthenticationSettingsService;
use Modules\UserManagement\Models\User;

/**
 * Service for authentication operations
 */
class AuthService
{
    /**
     * Authenticate user with email and password
     */
    public function login(string $email, string $password, bool $remember = false): User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user account is suspended
        if ($user->isSuspended()) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended. Please contact support.'],
            ]);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        return $user;
    }

    /**
     * Register a new user
     */
    public function register(string $name, string $email, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => null, // Email verification required
        ]);

        // Assign default customer role
        $user->assignRole('customer');

        return $user;
    }

    /**
     * Logout user (revoke all tokens)
     */
    public function logout(User $user): void
    {
        // Revoke all Sanctum tokens
        $user->tokens()->delete();
    }

    /**
     * Create Sanctum token for user
     */
    public function createToken(User $user, string $deviceName = 'web'): string
    {
        // Delete old tokens for this device (optional - for single device login)
        // $user->tokens()->where('name', $deviceName)->delete();

        // Get token expiry from database settings with fallback to config
        $settingsService = app(AuthenticationSettingsService::class);
        $expiryMinutes = $settingsService->get('token.sanctum_expiry', 1440); // Default 24 hours
        
        // Ensure expiryMinutes is numeric for Carbon (settings might return string)
        if (!is_numeric($expiryMinutes)) {
            $expiryMinutes = 1440;
        }
        $expiryMinutes = max(1, (int) $expiryMinutes);
        
        // Calculate expiration date
        $expiresAt = now()->addMinutes($expiryMinutes);

        $token = $user->createToken($deviceName, ['*'], $expiresAt)->plainTextToken;

        return $token;
    }
}
