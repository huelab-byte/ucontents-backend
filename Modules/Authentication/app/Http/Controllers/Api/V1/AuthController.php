<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authentication\Actions\LoginAction;
use Modules\Authentication\Actions\LogoutAction;
use Modules\Authentication\Actions\MagicLinkRequestAction;
use Modules\Authentication\Actions\MagicLinkVerifyAction;
use Modules\Authentication\Actions\OTPRequestAction;
use Modules\Authentication\Actions\OTPVerifyAction;
use Modules\Authentication\Actions\RegisterAction;
use Modules\Authentication\Services\AuthenticationSettingsService;
use Modules\Authentication\DTOs\LoginDTO;
use Modules\Authentication\DTOs\OTPVerifyDTO;
use Modules\Authentication\DTOs\RegisterDTO;
use Modules\Authentication\Http\Requests\LoginRequest;
use Modules\Authentication\Http\Requests\MagicLinkRequest;
use Modules\Authentication\Http\Requests\OTPRequest;
use Modules\Authentication\Http\Requests\RegisterRequest;
use Modules\Authentication\Http\Requests\VerifyMagicLinkRequest;
use Modules\Authentication\Http\Requests\VerifyOTPRequest;
use Modules\Authentication\Http\Resources\AuthFeaturesResource;
use Modules\Authentication\Http\Resources\AuthLoginResource;
use Modules\Authentication\Http\Resources\MagicLinkResponseResource;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\UserManagement\Http\Resources\UserResource;
use Modules\UserManagement\Models\User;

/**
 * Public Authentication Controller
 */
class AuthController extends BaseApiController
{
    public function __construct(
        private LoginAction $loginAction,
        private RegisterAction $registerAction,
        private LogoutAction $logoutAction,
        private MagicLinkRequestAction $magicLinkRequestAction,
        private MagicLinkVerifyAction $magicLinkVerifyAction,
        private OTPRequestAction $otpRequestAction,
        private OTPVerifyAction $otpVerifyAction,
        private AuthenticationSettingsService $settingsService
    ) {
    }

    /**
     * Get enabled authentication features
     */
    public function features(): JsonResponse
    {
        $socialAuthEnabled = $this->settingsService->get('features.social_auth.enabled', false);
        $socialAuthProviders = $socialAuthEnabled 
            ? $this->settingsService->get('features.social_auth.providers', [])
            : [];
        
        $features = [
            'magic_link' => [
                'enabled' => $this->settingsService->get('features.magic_link.enabled', false),
            ],
            'otp' => [
                'enabled' => $this->settingsService->get('features.otp_2fa.enabled', false),
            ],
            'social_auth' => [
                'enabled' => $socialAuthEnabled,
                'providers' => $socialAuthProviders,
            ],
            'email_verification' => [
                'enabled' => $this->settingsService->get('features.email_verification.enabled', false),
                'required' => $this->settingsService->get('features.email_verification.required', false),
            ],
            'password_reset' => [
                'enabled' => $this->settingsService->get('features.password_reset.enabled', false),
                'token_expiry' => (int) $this->settingsService->get('features.password_reset.token_expiry', 60),
            ],
            'password' => [
                'min_length' => (int) $this->settingsService->get('password.min_length', 8),
                'require_uppercase' => (bool) $this->settingsService->get('password.require_uppercase', true),
                'require_number' => (bool) $this->settingsService->get('password.require_number', true),
                'require_special' => (bool) $this->settingsService->get('password.require_special', false),
            ],
        ];

        return $this->success(new AuthFeaturesResource($features), 'Authentication features retrieved successfully');
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromArray($request->validated());
        $result = $this->loginAction->execute($dto);

        return $this->success(new AuthLoginResource($result), 'Login successful');
    }

    /**
     * Register new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromArray($request->validated());
        $user = $this->registerAction->execute($dto);

        return $this->success(new UserResource($user), 'Registration successful. Please verify your email.', 201);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $this->logoutAction->execute($request->user());

        return $this->success(null, 'Logout successful');
    }

    /**
     * Request magic link
     */
    public function requestMagicLink(MagicLinkRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $magicToken = $this->magicLinkRequestAction->execute(
            email: $validated['email'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->success(new MagicLinkResponseResource([
            'message' => 'Magic link sent to your email',
            'expires_at' => $magicToken->expires_at,
        ]), 'Magic link sent successfully');
    }

    /**
     * Verify magic link and login
     */
    public function verifyMagicLink(VerifyMagicLinkRequest $request): JsonResponse
    {
        $result = $this->magicLinkVerifyAction->execute($request->validated()['token']);

        return $this->success($result, 'Magic link verified successfully');
    }

    /**
     * Request OTP
     */
    public function requestOTP(OTPRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->firstOrFail();

        $otp = $this->otpRequestAction->execute(
            user: $user,
            type: $validated['type'] ?? 'login',
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->success([
            'message' => 'OTP sent to your email',
            'expires_at' => $otp->expires_at,
        ], 'OTP sent successfully');
    }

    /**
     * Verify OTP
     */
    public function verifyOTP(VerifyOTPRequest $request): JsonResponse
    {
        $dto = OTPVerifyDTO::fromArray($request->validated());
        $result = $this->otpVerifyAction->execute($dto);

        return $this->success($result, 'OTP verified successfully');
    }
}
