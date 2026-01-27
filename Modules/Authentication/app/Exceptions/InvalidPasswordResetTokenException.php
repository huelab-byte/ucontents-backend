<?php

declare(strict_types=1);

namespace Modules\Authentication\Exceptions;

/**
 * Exception thrown when password reset token is invalid or expired
 */
class InvalidPasswordResetTokenException extends PasswordResetException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Invalid or expired password reset token.')
    {
        parent::__construct($message, 400);
    }
}
