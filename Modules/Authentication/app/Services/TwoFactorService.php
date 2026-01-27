<?php

declare(strict_types=1);

namespace Modules\Authentication\Services;

use Illuminate\Support\Str;
use Modules\Authentication\Models\User2FASetting;
use Modules\UserManagement\Models\User;

/**
 * Service for Two-Factor Authentication operations
 * Note: This uses a simple secret-based approach. For production, consider using pragmarx/google2fa package
 */
class TwoFactorService
{
    /**
     * Generate a secret key for 2FA setup (32 characters base32 encoded)
     */
    public function generateSecret(): string
    {
        // Generate a random 20-byte secret and encode as base32
        $randomBytes = random_bytes(20);
        return $this->base32Encode($randomBytes);
    }

    /**
     * Get QR code URL for authenticator app
     * Format: otpauth://totp/{issuer}:{email}?secret={secret}&issuer={issuer}
     */
    public function getQRCodeUrl(string $email, string $secret, string $issuer = 'Your App'): string
    {
        $issuerEncoded = rawurlencode($issuer);
        $emailEncoded = rawurlencode($email);
        $secretEncoded = rawurlencode($secret);
        
        return "otpauth://totp/{$issuerEncoded}:{$emailEncoded}?secret={$secretEncoded}&issuer={$issuerEncoded}";
    }

    /**
     * Verify TOTP code (simplified implementation)
     * For production, use pragmarx/google2fa package
     */
    public function verifyCode(string $secret, string $code): bool
    {
        // Simple verification - in production, use proper TOTP algorithm
        // For now, we'll use a time-based approach with the OTP service
        // This is a placeholder - proper TOTP requires HMAC-SHA1 algorithm
        try {
            // Decode base32 secret
            $decodedSecret = $this->base32Decode($secret);
            
            // Get current time step (30 second windows)
            $timeStep = (int) floor(time() / 30);
            
            // Try current, previous, and next time steps (for clock skew)
            for ($i = -1; $i <= 1; $i++) {
                $testTimeStep = (int) ($timeStep + $i);
                $expectedCode = $this->generateTOTP($decodedSecret, $testTimeStep);
                if (hash_equals($expectedCode, $code)) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate TOTP code (simplified - for production use proper library)
     */
    private function generateTOTP(string $secret, int $timeStep): string
    {
        // Convert time step to 8-byte binary
        $time = pack('N*', 0) . pack('N*', $timeStep);
        
        // Generate HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secret, true);
        
        // Dynamic truncation
        $offset = ord($hmac[19]) & 0x0F;
        $code = (
            ((ord($hmac[$offset + 0]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        ) % 1000000;
        
        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 encode
     */
    private function base32Encode(string $data): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded = '';
        $bits = 0;
        $value = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $value = ($value << 8) | ord($data[$i]);
            $bits += 8;
            
            while ($bits >= 5) {
                $encoded .= $chars[($value >> ($bits - 5)) & 31];
                $bits -= 5;
            }
        }
        
        if ($bits > 0) {
            $encoded .= $chars[($value << (5 - $bits)) & 31];
        }
        
        return $encoded;
    }

    /**
     * Base32 decode
     */
    private function base32Decode(string $data): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $decoded = '';
        $bits = 0;
        $value = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            $pos = strpos($chars, $char);
            
            if ($pos === false) {
                continue;
            }
            
            $value = ($value << 5) | $pos;
            $bits += 5;
            
            if ($bits >= 8) {
                $decoded .= chr(($value >> ($bits - 8)) & 255);
                $bits -= 8;
            }
        }
        
        return $decoded;
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }
        return $codes;
    }

    /**
     * Get or create 2FA settings for user
     */
    public function getOrCreateSettings(User $user): User2FASetting
    {
        return User2FASetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'enabled' => false,
                'secret_key' => null,
                'backup_codes' => null,
            ]
        );
    }

    /**
     * Enable 2FA for user
     * Returns the generated backup codes
     */
    public function enable2FA(User $user, string $secret, string $verificationCode): array
    {
        $settings = $this->getOrCreateSettings($user);

        // Verify the code before enabling
        if (!$this->verifyCode($secret, $verificationCode)) {
            throw new \Exception('Invalid verification code.');
        }

        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();

        $settings->update([
            'enabled' => true,
            'secret_key' => encrypt($secret),
            'backup_codes' => $backupCodes,
            'enabled_at' => now(),
        ]);

        return $backupCodes;
    }

    /**
     * Disable 2FA for user
     */
    public function disable2FA(User $user): bool
    {
        $settings = $this->getOrCreateSettings($user);
        
        $settings->update([
            'enabled' => false,
            'secret_key' => null,
            'backup_codes' => null,
            'enabled_at' => null,
        ]);

        return true;
    }

    /**
     * Verify 2FA code for user
     */
    public function verifyUserCode(User $user, string $code): bool
    {
        $settings = $this->getOrCreateSettings($user);

        if (!$settings->enabled || !$settings->secret_key) {
            return false;
        }

        try {
            $secret = decrypt($settings->secret_key);

            // Try TOTP code first
            if ($this->verifyCode($secret, $code)) {
                return true;
            }

            // Try backup codes
            return $settings->useBackupCode($code);
        } catch (\Exception $e) {
            // If decryption fails, try backup codes
            return $settings->useBackupCode($code);
        }
    }

    /**
     * Check if 2FA is required for user based on role and settings
     * NOTE: 2FA requirement is now disabled - users can access without 2FA setup
     * 2FA verification is still enforced after login if user has 2FA enabled
     */
    public function is2FARequired(User $user): bool
    {
        // 2FA requirement is disabled - users are not forced to set up 2FA
        // However, if a user has 2FA enabled, they must provide the code after login
        return false;
    }

    /**
     * Check if user has 2FA enabled
     */
    public function is2FAEnabled(User $user): bool
    {
        $settings = User2FASetting::where('user_id', $user->id)->first();
        return $settings && $settings->enabled;
    }

    /**
     * Get 2FA status for user
     */
    public function get2FAStatus(User $user): array
    {
        $settings = $this->getOrCreateSettings($user);
        $isRequired = $this->is2FARequired($user);
        $isEnabled = $settings->enabled;

        return [
            'enabled' => $isEnabled,
            'required' => $isRequired,
            'enabled_at' => $settings->enabled_at?->toIso8601String(),
        ];
    }
}
