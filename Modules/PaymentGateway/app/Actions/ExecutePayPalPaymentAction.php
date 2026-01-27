<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Illuminate\Support\Facades\Log;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Services\Gateways\PayPalService;

/**
 * Action to execute a PayPal payment after user approval
 */
class ExecutePayPalPaymentAction
{
    /**
     * Execute the PayPal payment
     *
     * @throws \InvalidArgumentException if payment is not valid for execution
     * @throws \RuntimeException if payment execution fails
     */
    public function execute(Payment $payment, string $payerId): Payment
    {
        // Validate payment can be executed
        $this->validatePayment($payment);

        // Get gateway and execute payment
        $gateway = $payment->paymentGateway;
        if (!$gateway) {
            throw new \InvalidArgumentException('Payment gateway not found');
        }

        $paypalService = new PayPalService(
            $gateway->credentials['client_id'] ?? $gateway->credentials['test_client_id'] ?? '',
            $gateway->credentials['client_secret'] ?? $gateway->credentials['test_client_secret'] ?? '',
            $gateway->is_test_mode
        );

        try {
            $result = $paypalService->executePayment($payment->gateway_transaction_id, $payerId);

            // Update payment status
            $payment->status = 'completed';
            $payment->processed_at = now();
            $payment->gateway_response = array_merge($payment->gateway_response ?? [], $result);
            $payment->save();

            // Update associated invoice
            $this->updateInvoice($payment);

            return $payment;
        } catch (\Exception $e) {
            Log::error('PayPal payment execution failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Payment execution failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function validatePayment(Payment $payment): void
    {
        if ($payment->gateway_name !== 'paypal') {
            throw new \InvalidArgumentException('Payment is not a PayPal payment');
        }

        if ($payment->status !== 'pending') {
            throw new \InvalidArgumentException('Payment is not in pending status');
        }
    }

    private function updateInvoice(Payment $payment): void
    {
        if ($payment->invoice) {
            $payment->invoice->status = 'paid';
            $payment->invoice->paid_at = now();
            $payment->invoice->save();
        }
    }
}
