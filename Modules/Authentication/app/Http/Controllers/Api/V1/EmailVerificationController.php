<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Modules\Authentication\Http\Requests\ResendEmailVerificationRequest;
use Modules\Authentication\Http\Requests\VerifyEmailRequest;
use Modules\Authentication\Services\AuthenticationSettingsService;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\EmailManagement\Services\EmailService;
use Modules\UserManagement\Models\User;

/**
 * Email Verification Controller
 */
class EmailVerificationController extends BaseApiController
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    /**
     * Verify email address
     */
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        // Check if already verified
        if ($user->email_verified_at) {
            return $this->success([
                'user' => $user->load('roles'),
                'message' => 'Email already verified.',
            ], 'Email already verified');
        }

        // Verify the token (using hash-based verification)
        // Token is generated as: hash('sha256', $user->email . $user->created_at . config('app.key'))
        $expectedToken = hash('sha256', $user->email . $user->created_at . config('app.key'));

        if (!hash_equals($expectedToken, $validated['token'])) {
            return $this->error('Invalid or expired verification token.', 400);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Generate auth token for automatic login
        $token = $user->createToken('web')->plainTextToken;

        return $this->success([
            'user' => $user->load('roles'),
            'token' => $token,
            'message' => 'Email verified successfully.',
        ], 'Email verified successfully');
    }

    /**
     * Resend verification email
     */
    public function resend(ResendEmailVerificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->firstOrFail();

        \Log::info('Email verification resend request', [
            'email' => $user->email,
            'user_id' => $user->id,
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            'is_verified' => (bool) $user->email_verified_at,
        ]);

        // Check if already verified
        if ($user->email_verified_at) {
            \Log::info('Email verification resend skipped - user already verified', [
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at->toDateTimeString(),
            ]);
            // Return success with message instead of error - user is already verified
            return $this->success([
                'message' => 'Email is already verified.',
                'verified' => true,
            ], 'Email is already verified.');
        }

        // Check if email verification is enabled
        $settingsService = app(AuthenticationSettingsService::class);
        $emailVerificationEnabled = $settingsService->get('features.email_verification.enabled', false);

        if (!$emailVerificationEnabled) {
            return $this->error('Email verification is not enabled.', 400);
        }

        // Generate verification token
        $token = hash('sha256', $user->email . $user->created_at . config('app.key'));

        // Build verification URL
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $verificationUrl = "{$frontendUrl}/auth/verify-email?token={$token}&email=" . urlencode($user->email);

        // Send verification email using EmailManagement module (same pattern as magic link)
        try {
            \Log::info('Sending email verification email (resend)', [
                'email' => $user->email,
                'name' => $user->name,
                'verification_url' => $verificationUrl,
            ]);

            // Send email using Email Management module (same as magic link)
            $emailService = app(\Modules\EmailManagement\Services\EmailService::class);
            $emailLog = $emailService->sendEmailVerificationEmail(
                to: $user->email,
                name: $user->name,
                verificationUrl: $verificationUrl,
                useQueue: true // Use queue for async sending (same as magic link)
            );

            \Log::info('Email verification email queued successfully', [
                'email' => $user->email,
                'email_log_id' => $emailLog->id,
                'status' => $emailLog->status,
                'template_id' => $emailLog->email_template_id,
                'subject' => $emailLog->subject,
            ]);

            // Verify email log was created
            if (!$emailLog || !$emailLog->id) {
                throw new \Exception('Failed to create email log entry');
            }

            return $this->success([
                'message' => 'Verification email sent successfully.',
                'email_log_id' => $emailLog->id,
                'status' => $emailLog->status,
            ], 'Verification email sent successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to send email verification email (resend)', [
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Continue even if email fails - user can try again
            // In production, you might want to handle this differently
            return $this->error('Failed to send verification email: ' . $e->getMessage(), 500);
        }
    }
}
