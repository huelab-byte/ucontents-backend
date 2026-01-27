<?php

declare(strict_types=1);

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Authentication\Exceptions\InvalidPasswordResetTokenException;
use Modules\Authentication\Exceptions\PasswordResetThrottledException;
use Modules\Authentication\Exceptions\UserNotFoundException;
use Modules\Authentication\Models\MagicLinkToken;
use Modules\EmailManagement\Services\EmailService;
use Modules\UserManagement\Models\User;

/**
 * Service for token management (password reset, magic links)
 */
class TokenService
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    /**
     * Send password reset token
     */
    public function sendPasswordResetToken(string $email): string
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new UserNotFoundException('We could not find a user with that email address.');
        }

        // Check rate limiting - get from database settings with fallback to config
        $settingsService = app(\Modules\Authentication\Services\AuthenticationSettingsService::class);
        $rateLimit = $settingsService->get('features.password_reset.rate_limit', 3);
        $rateLimitPeriod = 60; // 1 hour in minutes
        
        // Count requests in the last hour
        $recentRequests = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('created_at', '>', now()->subMinutes($rateLimitPeriod))
            ->count();
        
        if ($recentRequests >= $rateLimit) {
            // Find the oldest request in the current hour window
            $oldestRequest = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('created_at', '>', now()->subMinutes($rateLimitPeriod))
                ->orderBy('created_at', 'asc')
                ->first();
            
            if ($oldestRequest) {
                // Calculate how many minutes ago the oldest request was created
                $oldestRequestTime = \Carbon\Carbon::parse($oldestRequest->created_at);
                $minutesAgo = now()->diffInMinutes($oldestRequestTime);
                // Calculate minutes remaining until the oldest request falls outside the window
                $minutesRemaining = $rateLimitPeriod - $minutesAgo;
                // Ensure it's between 1 and the rate limit period
                $minutesRemaining = max(1, min((int) ceil($minutesRemaining), $rateLimitPeriod));
                throw new PasswordResetThrottledException(
                    "Too many password reset requests. Please wait {$minutesRemaining} minute(s) before requesting another password reset. (Limit: {$rateLimit} per hour)"
                );
            }
        }

        // Create password reset token
        $token = Password::createToken($user);

        // Build reset URL
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $resetUrl = "{$frontendUrl}/auth/reset-password?token={$token}&email=" . urlencode($email);

        // Get expiration time from database settings with fallback to config
        $settingsService = app(\Modules\Authentication\Services\AuthenticationSettingsService::class);
        $expiresIn = $settingsService->get('features.password_reset.token_expiry', 60);
        
        // Ensure expiresIn is an integer (settings might return string)
        $expiresIn = (int) $expiresIn;

        // Send email using Email Management module
        try {
            $this->emailService->sendPasswordResetEmail(
                to: $email,
                name: $user->name,
                resetUrl: $resetUrl,
                expiresInMinutes: $expiresIn
            );
        } catch (\Exception $e) {
            // If email sending fails, delete the token
            Password::deleteToken($user);
            throw new \Exception('Unable to send password reset email. Please try again later.');
        }

        return Password::RESET_LINK_SENT;
    }

    /**
     * Reset password using token
     * 
     * This also marks the email as verified since the user has proven
     * they have access to the email address by using the reset link.
     */
    public function resetPassword(string $email, string $token, string $password, string $passwordConfirmation): void
    {
        $status = Password::reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
                'token' => $token,
            ],
            function ($user, $password) {
                // Set the new password
                // The 'hashed' cast in the User model will automatically hash it
                $user->password = $password;
                
                // Mark email as verified - user has proven they have access to the email
                // This handles both password resets and initial password setup for admin-created users
                if (!$user->email_verified_at) {
                    $user->email_verified_at = now();
                }
                
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            match ($status) {
                Password::INVALID_TOKEN => throw new InvalidPasswordResetTokenException(),
                Password::INVALID_USER => throw new UserNotFoundException(),
                Password::RESET_THROTTLED => throw new PasswordResetThrottledException(),
                default => throw new \Exception('Unable to reset password. Please try again.'),
            };
        }
    }

    /**
     * Generate magic link token
     */
    public function generateMagicLinkToken(string $email, ?string $ipAddress = null, ?string $userAgent = null): MagicLinkToken
    {
        // Check rate limiting - get from database settings with fallback to config
        $settingsService = app(\Modules\Authentication\Services\AuthenticationSettingsService::class);
        $rateLimit = $settingsService->get('features.magic_link.rate_limit', 3);
        $rateLimitPeriod = 60; // 1 hour in minutes
        
        // Count requests in the last hour
        $recentRequests = MagicLinkToken::where('email', $email)
            ->where('created_at', '>', now()->subMinutes($rateLimitPeriod))
            ->count();
        
        if ($recentRequests >= $rateLimit) {
            // Find the oldest request in the current hour window
            $oldestRequest = MagicLinkToken::where('email', $email)
                ->where('created_at', '>', now()->subMinutes($rateLimitPeriod))
                ->orderBy('created_at', 'asc')
                ->first();
            
            if ($oldestRequest) {
                // Calculate how many minutes ago the oldest request was created
                $minutesAgo = now()->diffInMinutes($oldestRequest->created_at);
                // Calculate minutes remaining until the oldest request falls outside the window
                $minutesRemaining = $rateLimitPeriod - $minutesAgo;
                // Ensure it's between 1 and the rate limit period
                $minutesRemaining = max(1, min((int) ceil($minutesRemaining), $rateLimitPeriod));
                throw new \Exception(
                    "Too many magic link requests. Please wait {$minutesRemaining} minute(s) before requesting another magic link. (Limit: {$rateLimit} per hour)"
                );
            }
        }

        // Delete old unused tokens for this email
        MagicLinkToken::where('email', $email)
            ->where('used', false)
            ->where('expires_at', '<', now())
            ->delete();

        // Get token expiry from database settings with fallback to config
        $settingsService = app(\Modules\Authentication\Services\AuthenticationSettingsService::class);
        $tokenExpiry = $settingsService->get('features.magic_link.token_expiry', 15);
        
        // Ensure tokenExpiry is numeric for Carbon (settings might return string)
        if (!is_numeric($tokenExpiry)) {
            $tokenExpiry = 15;
        }
        $tokenExpiry = max(1, (int) $tokenExpiry);
        
        $token = MagicLinkToken::create([
            'email' => $email,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes($tokenExpiry),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return $token;
    }

    /**
     * Verify and use magic link token
     */
    public function verifyMagicLinkToken(string $token): User
    {
        $magicToken = MagicLinkToken::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$magicToken) {
            throw new \Exception('Invalid or expired magic link token.');
        }

        $user = User::where('email', $magicToken->email)->first();

        if (!$user) {
            throw new \Exception('User not found for this magic link.');
        }

        // Mark token as used
        $magicToken->markAsUsed();

        return $user;
    }
}
