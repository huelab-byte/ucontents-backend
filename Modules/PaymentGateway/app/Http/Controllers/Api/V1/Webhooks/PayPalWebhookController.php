<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Models\PaymentGateway;
use Modules\PaymentGateway\Services\PaymentGatewayService;

/**
 * PayPal Webhook Controller
 * 
 * Handles incoming webhook events from PayPal
 */
class PayPalWebhookController extends BaseApiController
{
    public function __construct(
        private PaymentGatewayService $gatewayService
    ) {
    }

    /**
     * Handle PayPal webhook
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();

        // Get PayPal gateway
        $gateway = PaymentGateway::where('name', 'paypal')
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            Log::warning('PayPal webhook received but gateway not configured');
            return $this->error('Gateway not configured', 400);
        }

        // Verify webhook signature (simplified - PayPal requires more complex verification)
        // In production, implement proper PayPal webhook verification
        $webhookId = config('paymentgateway.paypal.webhook_id');
        if (!$webhookId) {
            Log::warning('PayPal webhook ID not configured');
            // For development, allow without verification
            if (!config('app.debug')) {
                return $this->error('Webhook verification failed', 400);
            }
        }

        try {
            $event = \json_decode($payload, true);

            if (!isset($event['event_type'])) {
                return $this->error('Invalid event data', 400);
            }

            // Handle different event types
            match ($event['event_type']) {
                'PAYMENT.SALE.COMPLETED' => $this->handlePaymentCompleted($event),
                'PAYMENT.SALE.DENIED' => $this->handlePaymentDenied($event),
                'PAYMENT.CAPTURE.COMPLETED' => $this->handlePaymentCompleted($event),
                'PAYMENT.CAPTURE.DENIED' => $this->handlePaymentDenied($event),
                'PAYMENT.CAPTURE.REFUNDED' => $this->handleRefunded($event),
                default => Log::info('Unhandled PayPal webhook event', ['type' => $event['event_type']]),
            };

            return $this->success(['received' => true], 'Webhook processed successfully');
        } catch (\Exception $e) {
            Log::error('PayPal webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Webhook processing failed', 500);
        }
    }

    /**
     * Handle payment completed event
     */
    private function handlePaymentCompleted(array $event): void
    {
        $resource = $event['resource'] ?? [];
        $transactionId = $resource['id'] ?? $resource['parent_payment'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('gateway_transaction_id', $transactionId)->first();

            if ($payment) {
                $payment->status = 'completed';
                $payment->processed_at = now();
                $payment->gateway_response = array_merge($payment->gateway_response ?? [], [
                    'webhook_event' => $event,
                ]);
                $payment->save();

                // Update invoice
                if ($payment->invoice) {
                    $payment->invoice->status = 'paid';
                    $payment->invoice->paid_at = now();
                    $payment->invoice->save();
                }
            }
        }
    }

    /**
     * Handle payment denied event
     */
    private function handlePaymentDenied(array $event): void
    {
        $resource = $event['resource'] ?? [];
        $transactionId = $resource['id'] ?? $resource['parent_payment'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('gateway_transaction_id', $transactionId)->first();

            if ($payment) {
                $payment->status = 'failed';
                $payment->failure_reason = $resource['reason_code'] ?? 'Payment denied';
                $payment->gateway_response = array_merge($payment->gateway_response ?? [], [
                    'webhook_event' => $event,
                ]);
                $payment->save();
            }
        }
    }

    /**
     * Handle refunded event
     */
    private function handleRefunded(array $event): void
    {
        $resource = $event['resource'] ?? [];
        $saleId = $resource['id'] ?? null;
        $parentPayment = $resource['parent_payment'] ?? null;

        if ($parentPayment) {
            $payment = Payment::where('gateway_transaction_id', $parentPayment)->first();

            if ($payment) {
                // Update payment status if fully refunded
                $refundAmount = isset($resource['amount']['total']) 
                    ? (float) $resource['amount']['total'] 
                    : 0;

                $totalRefunded = $payment->refunds()
                    ->where('status', 'completed')
                    ->sum('amount');

                if (($totalRefunded + $refundAmount) >= $payment->amount) {
                    $payment->status = 'refunded';
                    $payment->save();
                }

                // Update invoice if fully refunded
                if ($payment->invoice) {
                    $totalInvoiceRefunded = $payment->invoice->refunds()
                        ->where('status', 'completed')
                        ->sum('amount');

                    if ($totalInvoiceRefunded >= $payment->invoice->total) {
                        $payment->invoice->status = 'refunded';
                        $payment->invoice->save();
                    }
                }
            }
        }
    }
}
