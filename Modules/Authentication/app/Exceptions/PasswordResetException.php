<?php

declare(strict_types=1);

namespace Modules\Authentication\Exceptions;

use Exception;

/**
 * Base exception for password reset operations
 */
class PasswordResetException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Password reset failed.', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
