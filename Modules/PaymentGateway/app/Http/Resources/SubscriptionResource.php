<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_number' => $this->subscription_number,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'interval' => $this->interval,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'next_billing_date' => $this->next_billing_date,
            'last_payment_date' => $this->last_payment_date,
            'gateway_subscription_id' => $this->gateway_subscription_id,
            'is_active' => $this->isActive(),
            'is_due_for_billing' => $this->isDueForBilling(),
            'payment_gateway' => new PaymentGatewayResource($this->whenLoaded('paymentGateway')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
