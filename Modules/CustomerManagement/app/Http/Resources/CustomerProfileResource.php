<?php

declare(strict_types=1);

namespace Modules\CustomerManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PaymentGateway\Http\Resources\InvoiceResource;
use Modules\PaymentGateway\Http\Resources\PaymentResource;
use Modules\PaymentGateway\Http\Resources\SubscriptionResource;
use Modules\UserManagement\Http\Resources\UserResource;

class CustomerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $data = $this->resource['data'];

        return [
            'user' => (new UserResource($user))->toArray($request),
            'invoices_count' => $data->invoicesCount,
            'payments_count' => $data->paymentsCount,
            'support_tickets_count' => $data->supportTicketsCount,
            'active_subscriptions' => SubscriptionResource::collection($data->activeSubscriptions),
            'last_invoices' => InvoiceResource::collection($data->lastInvoices),
            'last_payments' => PaymentResource::collection($data->lastPayments),
        ];
    }
}
