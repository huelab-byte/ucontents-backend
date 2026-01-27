<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\Models\OtpCode;
use Modules\Authentication\Services\OTPService;
use Modules\UserManagement\Models\User;

/**
 * Action to request OTP
 */
class OTPRequestAction
{
    public function __construct(
        private OTPService $otpService
    ) {
    }

    public function execute(User $user, string $type = 'login', ?string $ipAddress = null, ?string $userAgent = null): OtpCode
    {
        return $this->otpService->generateOTP($user, $type, $ipAddress, $userAgent);
    }
}
