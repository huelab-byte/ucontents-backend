<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authentication\Http\Requests\TwoFactorDisableRequest;
use Modules\Authentication\Http\Requests\TwoFactorEnableRequest;
use Modules\Authentication\Http\Requests\TwoFactorVerifyRequest;
use Modules\Authentication\Services\TwoFactorService;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\UserManagement\Models\User;

/**
 * Two-Factor Authentication Controller
 */
class TwoFactorController extends BaseApiController
{
    public function __construct(
        private TwoFactorService $twoFactorService
    ) {
    }

    /**
     * Get 2FA status for current user
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $this->twoFactorService->get2FAStatus($user);

        return $this->success($status, '2FA status retrieved successfully');
    }

    /**
     * Get backup codes for current user
     */
    public function backupCodes(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $this->twoFactorService->getOrCreateSettings($user);

        if (!$settings->enabled) {
            return $this->error('2FA is not enabled for this account.', 400);
        }

        // Make backup codes visible (they're hidden by default in the model)
        $settings->makeVisible('backup_codes');
        $backupCodes = $settings->backup_codes ?? [];

        return $this->success([
            'backup_codes' => $backupCodes,
        ], 'Backup codes retrieved successfully');
    }

    /**
     * Generate secret and QR code for 2FA setup
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Generate secret
        $secret = $this->twoFactorService->generateSecret();
        
        // Get QR code URL - use site name from General Settings
        $appName = 'Your App';
        try {
            if (class_exists(\Modules\GeneralSettings\Services\GeneralSettingsService::class)) {
                $settingsService = app(\Modules\GeneralSettings\Services\GeneralSettingsService::class);
                $siteName = $settingsService->get('branding.site_name');
                if (!empty($siteName)) {
                    $appName = $siteName;
                }
            }
        } catch (\Exception $e) {
            // Fallback to config
            $appName = config('app.name', 'Your App');
        }
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl($user->email, $secret, $appName);

        return $this->success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ], '2FA setup data generated successfully');
    }

    /**
     * Enable 2FA for current user
     */
    public function enable(TwoFactorEnableRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        try {
            // Enable 2FA and get backup codes directly from the service
            $backupCodes = $this->twoFactorService->enable2FA(
                $user,
                $validated['secret'],
                $validated['code']
            );

            return $this->success([
                'enabled' => true,
                'backup_codes' => $backupCodes,
            ], '2FA enabled successfully. Please save your backup codes.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Disable 2FA for current user
     */
    public function disable(TwoFactorDisableRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // Verify code before disabling
        if (!$this->twoFactorService->verifyUserCode($user, $validated['code'])) {
            return $this->error('Invalid verification code.', 400);
        }

        $this->twoFactorService->disable2FA($user);

        return $this->success(['enabled' => false], '2FA disabled successfully');
    }

    /**
     * Verify 2FA code (for login)
     */
    public function verify(TwoFactorVerifyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        if (!$this->twoFactorService->verifyUserCode($user, $validated['code'])) {
            return $this->error('Invalid verification code.', 400);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Create token for user
        $authService = app(\Modules\Authentication\Services\AuthService::class);
        $token = $authService->createToken($user, 'web');

        return $this->success([
            'user' => $user->load('roles.permissions'),
            'token' => $token,
        ], '2FA verified successfully');
    }
}
