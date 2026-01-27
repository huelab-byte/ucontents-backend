<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\Models\Invoice;

/**
 * Action to delete an invoice
 */
class DeleteInvoiceAction
{
    /**
     * @throws \Exception
     */
    public function execute(Invoice $invoice): bool
    {
        if ($invoice->isPaid()) {
            throw new \Exception('Cannot delete a paid invoice.');
        }

        return $invoice->delete();
    }
}
