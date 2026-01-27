<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\DTOs\RegisterDTO;
use Modules\Authentication\Services\AuthService;
use Modules\Authentication\Services\AuthenticationSettingsService;
use Modules\EmailManagement\Services\EmailService;
use Modules\UserManagement\Models\User;

/**
 * Action to register a new user
 */
class RegisterAction
{
    public function __construct(
        private AuthService $authService,
        private EmailService $emailService,
        private AuthenticationSettingsService $settingsService
    ) {
    }

    public function execute(RegisterDTO $dto): User
    {
        $user = $this->authService->register(
            name: $dto->name,
            email: $dto->email,
            password: $dto->password
        );

        // Check if email verification is enabled
        $emailVerificationEnabled = $this->settingsService->get('features.email_verification.enabled', false);
        
        \Log::info('Registration - Email verification check', [
            'enabled' => $emailVerificationEnabled,
            'type' => gettype($emailVerificationEnabled),
        ]);

        if ($emailVerificationEnabled) {
            // Generate verification token
            $token = hash('sha256', $user->email . $user->created_at . config('app.key'));

            // Build verification URL
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            $verificationUrl = "{$frontendUrl}/auth/verify-email?token={$token}&email=" . urlencode($user->email);

            // Send verification email using EmailManagement module (same pattern as magic link)
            try {
                \Log::info('Sending email verification email during registration', [
                    'email' => $user->email,
                    'name' => $user->name,
                ]);

                // Send email using Email Management module (same as magic link)
                $emailService = app(\Modules\EmailManagement\Services\EmailService::class);
                $emailLog = $emailService->sendEmailVerificationEmail(
                    to: $user->email,
                    name: $user->name,
                    verificationUrl: $verificationUrl,
                    useQueue: true // Use queue for async sending (same as magic link)
                );

                \Log::info('Email verification email queued during registration', [
                    'email' => $user->email,
                    'email_log_id' => $emailLog->id,
                    'status' => $emailLog->status,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to send email verification email during registration', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Don't fail registration if email sending fails
            }
        }

        return $user->load('roles');
    }
}
