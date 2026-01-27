<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Illuminate\Support\Facades\Log;
use Modules\Authentication\Models\MagicLinkToken;
use Modules\Authentication\Services\AuthenticationSettingsService;
use Modules\Authentication\Services\TokenService;
use Modules\EmailManagement\Services\EmailService;
use Modules\UserManagement\Models\User;

/**
 * Action to request magic link
 */
class MagicLinkRequestAction
{
    public function __construct(
        private TokenService $tokenService,
        private AuthenticationSettingsService $settingsService,
        private EmailService $emailService
    ) {
    }

    public function execute(string $email, ?string $ipAddress = null, ?string $userAgent = null): MagicLinkToken
    {
        $magicToken = $this->tokenService->generateMagicLinkToken($email, $ipAddress, $userAgent);

        $this->sendMagicLinkEmail($email, $magicToken);

        return $magicToken;
    }

    private function sendMagicLinkEmail(string $email, MagicLinkToken $magicToken): void
    {
        try {
            // Build magic link URL
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            $magicLinkUrl = "{$frontendUrl}/auth/magic-link/verify?token={$magicToken->token}&email=" . urlencode($email);

            // Get expiration time from database settings with fallback to config
            $expiresIn = (int) $this->settingsService->get('features.magic_link.token_expiry', 15);

            // Try to find user by email to get name
            $user = User::where('email', $email)->first();
            $userName = $user ? $user->name : explode('@', $email)[0];

            // Send email using Email Management module
            $this->emailService->sendMagicLinkEmail(
                to: $email,
                name: $userName,
                magicLink: $magicLinkUrl,
                expiresInMinutes: $expiresIn
            );
        } catch (\Exception $e) {
            Log::error('Failed to send magic link email', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Continue even if email fails - token is still generated
            // In production, you might want to throw or handle this differently
        }
    }
}
