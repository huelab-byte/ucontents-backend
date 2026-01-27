<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'payment_number' => $this->payment_number,
            'invoice_id' => $this->invoice_id,
            'user_id' => $this->user_id,
            'payment_gateway_id' => $this->payment_gateway_id,
            'gateway_name' => $this->gateway_name,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'processed_at' => $this->processed_at,
            'failure_reason' => $this->failure_reason,
            'can_be_refunded' => $this->canBeRefunded(),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'payment_gateway' => new PaymentGatewayResource($this->whenLoaded('paymentGateway')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
