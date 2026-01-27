<?php

declare(strict_types=1);

namespace Modules\Authentication\Exceptions;

/**
 * Exception thrown when user is not found for password reset
 */
class UserNotFoundException extends PasswordResetException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'User not found.')
    {
        parent::__construct($message, 404);
    }
}
