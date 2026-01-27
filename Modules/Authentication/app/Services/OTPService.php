<?php

declare(strict_types=1);

namespace Modules\Authentication\Services;

use Illuminate\Support\Str;
use Modules\Authentication\Models\OtpCode;
use Modules\UserManagement\Models\User;

/**
 * Service for OTP operations
 */
class OTPService
{
    /**
     * Generate and send OTP code
     */
    public function generateOTP(User $user, string $type = 'login', ?string $ipAddress = null, ?string $userAgent = null): OtpCode
    {
        // Delete old unused OTPs for this user and type
        OtpCode::where('user_id', $user->id)
            ->where('type', $type)
            ->where('used', false)
            ->where('expires_at', '<', now())
            ->delete();

        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes(10), // 10 minutes expiry
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // TODO: Send OTP via email/SMS
        // Mail::to($user->email)->send(new OTPSentMail($code));

        return $otp;
    }

    /**
     * Verify OTP code
     */
    public function verifyOTP(string $code, ?User $user = null, ?string $email = null, string $type = 'login'): User
    {
        $query = OtpCode::where('code', $code)
            ->where('type', $type)
            ->where('used', false)
            ->where('expires_at', '>', now());

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $otp = $query->first();

        if (!$otp) {
            throw new \Exception('Invalid or expired OTP code.');
        }

        $otpUser = $otp->user;

        if ($email && $otpUser->email !== $email) {
            throw new \Exception('OTP code does not match the provided email.');
        }

        // Mark OTP as used
        $otp->markAsUsed();

        return $otpUser;
    }
}
