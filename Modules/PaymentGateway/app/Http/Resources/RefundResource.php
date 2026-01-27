<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
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
            'refund_number' => $this->refund_number,
            'payment_id' => $this->payment_id,
            'invoice_id' => $this->invoice_id,
            'user_id' => $this->user_id,
            'payment_gateway_id' => $this->payment_gateway_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'gateway_refund_id' => $this->gateway_refund_id,
            'reason' => $this->reason,
            'processed_at' => $this->processed_at,
            'failure_reason' => $this->failure_reason,
            'is_completed' => $this->isCompleted(),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'payment_gateway' => new PaymentGatewayResource($this->whenLoaded('paymentGateway')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
