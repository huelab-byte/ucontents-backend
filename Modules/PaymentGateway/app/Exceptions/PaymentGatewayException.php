<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Exceptions;

use Exception;

/**
 * Base exception for payment gateway errors
 */
class PaymentGatewayException extends Exception
{
    protected array $gatewayResponse = [];

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, array $gatewayResponse = [])
    {
        parent::__construct($message, $code, $previous);
        $this->gatewayResponse = $gatewayResponse;
    }

    public function getGatewayResponse(): array
    {
        return $this->gatewayResponse;
    }
}
