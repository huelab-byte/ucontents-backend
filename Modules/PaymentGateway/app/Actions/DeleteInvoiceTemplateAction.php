<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\Models\InvoiceTemplate;

/**
 * Action to delete an invoice template
 */
class DeleteInvoiceTemplateAction
{
    public function execute(InvoiceTemplate $invoiceTemplate): bool
    {
        return $invoiceTemplate->delete();
    }
}
