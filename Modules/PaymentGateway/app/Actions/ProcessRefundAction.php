<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\DTOs\RefundDTO;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Models\Refund;
use Modules\PaymentGateway\Services\PaymentGatewayService;

/**
 * Action to process a refund
 */
class ProcessRefundAction
{
    public function __construct(
        private PaymentGatewayService $gatewayService
    ) {
    }

    public function execute(RefundDTO $dto, ?int $processedBy = null): Refund
    {
        $payment = Payment::findOrFail($dto->paymentId);

        // Validate refund amount
        $totalRefunded = $payment->refunds()
            ->where('status', 'completed')
            ->sum('amount');

        if (($totalRefunded + $dto->amount) > $payment->amount) {
            throw new \InvalidArgumentException('Refund amount exceeds payment amount');
        }

        // Get payment gateway
        $gateway = $payment->paymentGateway;

        // Create refund record
        $refund = new Refund();
        $refund->refund_number = $this->generateRefundNumber();
        $refund->payment_id = $payment->id;
        $refund->invoice_id = $payment->invoice_id;
        $refund->user_id = $dto->userId;
        $refund->payment_gateway_id = $gateway?->id;
        $refund->amount = $dto->amount;
        $refund->currency = $payment->currency;
        $refund->status = 'processing';
        $refund->reason = $dto->reason;
        $refund->metadata = $dto->metadata;
        $refund->processed_by = $processedBy;
        $refund->save();

        try {
            // Prepare refund data based on gateway
            $refundData = [
                'amount' => $dto->amount,
                'currency' => $payment->currency,
                'reason' => $dto->reason,
            ];

            // Add gateway-specific transaction ID
            if ($gateway->name === 'stripe') {
                $refundData['payment_intent_id'] = $payment->gateway_transaction_id;
            } elseif ($gateway->name === 'paypal') {
                // PayPal requires sale_id - you may need to store this separately
                $refundData['sale_id'] = $payment->gateway_transaction_id;
            } else {
                $refundData['payment_transaction_id'] = $payment->gateway_transaction_id;
            }

            // Process refund through gateway
            $gatewayResponse = $this->gatewayService->processRefund($gateway, $refundData);

            // Update refund with gateway response
            $refund->gateway_refund_id = $gatewayResponse['refund_id'] ?? null;
            $refund->gateway_response = $gatewayResponse;
            
            // Map gateway status
            $gatewayStatus = $gatewayResponse['status'] ?? 'failed';
            $refund->status = match ($gatewayStatus) {
                'completed' => 'completed',
                'processing' => 'processing',
                default => 'failed',
            };
            
            $refund->processed_at = now();

            if ($refund->status === 'completed') {
                // Update payment status if fully refunded
                $newTotalRefunded = $totalRefunded + $dto->amount;
                if ($newTotalRefunded >= $payment->amount) {
                    $payment->status = 'refunded';
                    $payment->save();
                }

                // Update invoice status if fully refunded
                $invoice = $payment->invoice;
                $totalInvoiceRefunded = $invoice->refunds()
                    ->where('status', 'completed')
                    ->sum('amount');
                
                if ($totalInvoiceRefunded >= $invoice->total) {
                    $invoice->status = 'refunded';
                    $invoice->save();
                }
            }
        } catch (\Modules\PaymentGateway\Exceptions\RefundProcessingException $e) {
            $refund->status = 'failed';
            $refund->failure_reason = $e->getMessage();
            $refund->gateway_response = array_merge($refund->gateway_response ?? [], [
                'error' => $e->getMessage(),
                'gateway_response' => $e->getGatewayResponse(),
            ]);
            \Log::error('Refund processing failed', [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'gateway' => $gateway?->name,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $refund->status = 'failed';
            $refund->failure_reason = $e->getMessage();
            \Log::error('Unexpected refund processing error', [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'gateway' => $gateway?->name,
                'error' => $e->getMessage(),
            ]);
        }

        $refund->save();

        return $refund;
    }

    /**
     * Generate a unique refund number
     */
    private function generateRefundNumber(): string
    {
        $prefix = 'REF-';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . $date . '-' . $random;
    }
}
