<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\DTOs\CreateInvoiceDTO;
use Modules\PaymentGateway\Models\Invoice;

/**
 * Action to generate an invoice
 */
class GenerateInvoiceAction
{
    public function execute(CreateInvoiceDTO $dto, ?int $createdBy = null): Invoice
    {
        $total = $dto->subtotal + $dto->tax - $dto->discount;

        $invoice = new Invoice();
        $invoice->invoice_number = $this->generateInvoiceNumber();
        $invoice->user_id = $dto->userId;
        $invoice->type = $dto->type;
        $invoice->subtotal = $dto->subtotal;
        $invoice->tax = $dto->tax;
        $invoice->discount = $dto->discount;
        $invoice->total = $total;
        $invoice->currency = $dto->currency;
        $invoice->status = 'draft';
        $invoice->due_date = $dto->dueDate;
        $invoice->notes = $dto->notes;
        $invoice->metadata = $dto->metadata;
        $invoice->created_by = $createdBy;

        if ($dto->invoiceableType && $dto->invoiceableId) {
            $invoice->invoiceable_type = $dto->invoiceableType;
            $invoice->invoiceable_id = $dto->invoiceableId;
        }

        $invoice->save();

        return $invoice;
    }

    /**
     * Generate a unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . $date . '-' . $random;
    }
}
