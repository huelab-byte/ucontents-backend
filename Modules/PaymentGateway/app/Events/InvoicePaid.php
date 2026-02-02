<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\PaymentGateway\Models\Invoice;

class InvoicePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {
    }
}
