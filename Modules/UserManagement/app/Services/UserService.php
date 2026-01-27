<?php

declare(strict_types=1);

namespace Modules\UserManagement\Services;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\EmailManagement\Services\EmailService;
use Modules\UserManagement\Models\User;

/**
 * Service for user management operations
 */
class UserService
{
    public function __construct(
        private ?EmailService $emailService = null
    ) {
        // EmailService is optional - will be injected if EmailManagement module is available
    }

    /**
     * Create a new user with roles
     * 
     * @param string $name User's name
     * @param string $email User's email
     * @param string|null $password Password (if null, a random one is generated)
     * @param array|null $roleSlugs Roles to assign
     * @param string $status User status
     * @param bool $sendSetPasswordEmail Whether to send set password email
     */
    public function createUser(
        string $name,
        string $email,
        ?string $password = null,
        ?array $roleSlugs = null,
        string $status = User::STATUS_ACTIVE,
        bool $sendSetPasswordEmail = true
    ): User {
        // Generate a random password if not provided
        $actualPassword = $password ?? Str::random(32);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $actualPassword,
            'status' => $status,
        ]);

        if ($roleSlugs) {
            $user->syncRoles($roleSlugs);
        }

        // Send set password email if no password was provided
        if ($sendSetPasswordEmail && $password === null) {
            $this->sendSetPasswordEmail($user);
        }

        return $user->load('roles');
    }

    /**
     * Send set password email to user using EmailManagement module
     */
    public function sendSetPasswordEmail(User $user): void
    {
        // Generate password reset token
        $token = Password::broker()->createToken($user);
        
        // Build set password URL
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $setPasswordUrl = "{$frontendUrl}/auth/set-password?token={$token}&email=" . urlencode($user->email);
        $expiresInMinutes = (int) config('auth.passwords.users.expire', 60);

        // Use EmailService if available, otherwise fall back to notification
        if ($this->emailService) {
            $this->emailService->sendSetPasswordEmail(
                to: $user->email,
                name: $user->name,
                setPasswordUrl: $setPasswordUrl,
                expiresInMinutes: $expiresInMinutes,
            );
        } else {
            // Fallback to notification if EmailService is not available
            $user->sendSetPasswordNotification($token);
        }
    }

    /**
     * Update user information
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'password' => $data['password'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user->fresh()->load('roles');
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, string $roleSlug): void
    {
        $user->assignRole($roleSlug);
    }

    /**
     * Remove role from user
     */
    public function removeRole(User $user, string $roleSlug): void
    {
        $user->removeRole($roleSlug);
    }

    /**
     * Sync user roles
     */
    public function syncRoles(User $user, array $roleSlugs): void
    {
        $user->syncRoles($roleSlugs);
    }
}
