<?php

declare(strict_types=1);

namespace Modules\Authentication\Exceptions;

/**
 * Exception thrown when password reset is throttled
 */
class PasswordResetThrottledException extends PasswordResetException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Too many password reset attempts. Please try again later.')
    {
        parent::__construct($message, 429);
    }
}
