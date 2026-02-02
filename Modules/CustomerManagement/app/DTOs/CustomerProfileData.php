<?php

declare(strict_types=1);

namespace Modules\CustomerManagement\DTOs;

use Illuminate\Support\Collection;
use Modules\PaymentGateway\Models\Invoice;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Models\Subscription;
use Modules\UserManagement\Models\User;

readonly class CustomerProfileData
{
    public function __construct(
        public User $user,
        public int $invoicesCount,
        public int $paymentsCount,
        /** @var Collection<int, Subscription> */
        public Collection $activeSubscriptions,
        /** @var Collection<int, Invoice> */
        public Collection $lastInvoices,
        /** @var Collection<int, Payment> */
        public Collection $lastPayments,
        public int $supportTicketsCount
    ) {
    }
}
