<?php

declare(strict_types=1);

namespace Modules\Authentication\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Authentication\Services\AuthenticationSettingsService;

/**
 * Password complexity validation rule
 * Validates password against configured requirements from settings
 */
class PasswordComplexity implements Rule
{
    private string $message = '';
    private AuthenticationSettingsService $settingsService;

    public function __construct()
    {
        $this->settingsService = app(AuthenticationSettingsService::class);
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            $this->message = 'The password must be a string.';
            return false;
        }

        $minLength = (int) $this->settingsService->get('password.min_length', 8);
        $requireUppercase = (bool) $this->settingsService->get('password.require_uppercase', true);
        $requireNumber = (bool) $this->settingsService->get('password.require_number', true);
        $requireSpecial = (bool) $this->settingsService->get('password.require_special', false);

        // Check minimum length
        if (strlen($value) < $minLength) {
            $this->message = "The password must be at least {$minLength} characters.";
            return false;
        }

        // Check uppercase requirement
        if ($requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $this->message = 'The password must contain at least one uppercase letter.';
            return false;
        }

        // Check number requirement
        if ($requireNumber && !preg_match('/[0-9]/', $value)) {
            $this->message = 'The password must contain at least one number.';
            return false;
        }

        // Check special character requirement
        if ($requireSpecial && !preg_match('/[^a-zA-Z0-9]/', $value)) {
            $this->message = 'The password must contain at least one special character.';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message;
    }
}
