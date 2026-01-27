<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\Models\PaymentGateway;

/**
 * Action to delete a payment gateway
 */
class DeletePaymentGatewayAction
{
    public function execute(PaymentGateway $paymentGateway): bool
    {
        return $paymentGateway->delete();
    }
}
