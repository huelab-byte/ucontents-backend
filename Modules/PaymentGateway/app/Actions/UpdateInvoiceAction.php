<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\DTOs\UpdateInvoiceDTO;
use Modules\PaymentGateway\Models\Invoice;

/**
 * Action to update an invoice
 */
class UpdateInvoiceAction
{
    public function execute(Invoice $invoice, UpdateInvoiceDTO $dto): Invoice
    {
        // Only allow editing if invoice is not paid
        if ($invoice->isPaid()) {
            throw new \InvalidArgumentException('Cannot edit a paid invoice');
        }

        if ($dto->subtotal !== null) {
            $invoice->subtotal = $dto->subtotal;
        }

        if ($dto->tax !== null) {
            $invoice->tax = $dto->tax;
        }

        if ($dto->discount !== null) {
            $invoice->discount = $dto->discount;
        }

        // Recalculate total if any amount fields changed
        if ($dto->subtotal !== null || $dto->tax !== null || $dto->discount !== null) {
            $invoice->total = $invoice->subtotal + $invoice->tax - $invoice->discount;
        }

        if ($dto->status !== null) {
            $invoice->status = $dto->status;
        }

        if ($dto->dueDate !== null) {
            $invoice->due_date = $dto->dueDate;
        }

        if ($dto->notes !== null) {
            $invoice->notes = $dto->notes;
        }

        if ($dto->metadata !== null) {
            $invoice->metadata = $dto->metadata;
        }

        $invoice->save();

        return $invoice;
    }
}
