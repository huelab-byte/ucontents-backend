<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\DTOs\ProcessPaymentDTO;
use Modules\PaymentGateway\Models\Invoice;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Models\PaymentGateway;
use Modules\PaymentGateway\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Log;

/**
 * Action to process a payment
 */
class ProcessPaymentAction
{
    public function __construct(
        private PaymentGatewayService $gatewayService
    ) {
    }

    public function execute(ProcessPaymentDTO $dto): Payment
    {
        $invoice = Invoice::findOrFail($dto->invoiceId);

        // Get payment gateway
        $gateway = null;
        if ($dto->paymentGatewayId) {
            $gateway = PaymentGateway::findOrFail($dto->paymentGatewayId);
        } else {
            $gateway = PaymentGateway::where('name', $dto->gatewayName)
                ->where('is_active', true)
                ->firstOrFail();
        }

        // Create payment record
        $payment = new Payment();
        $payment->payment_number = $this->generatePaymentNumber();
        $payment->invoice_id = $invoice->id;
        $payment->user_id = $dto->userId;
        $payment->payment_gateway_id = $gateway->id;
        $payment->gateway_name = $gateway->name;
        $payment->amount = $invoice->total;
        $payment->currency = $invoice->currency;
        $payment->status = 'processing';
        $payment->payment_method = $dto->paymentMethod;
        $payment->metadata = $dto->metadata;
        $payment->save();

        try {
            // Prepare payment data
            $paymentData = [
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
                'invoice_id' => $invoice->id,
                'customer_email' => $payment->user->email ?? null,
                'description' => "Invoice #{$invoice->invoice_number}",
            ];

            // Add gateway-specific data
            if ($dto->gatewayData) {
                $paymentData = array_merge($paymentData, $dto->gatewayData);
            }

            // Process payment through gateway
            $gatewayResponse = $this->gatewayService->processPayment($gateway, $paymentData);

            // Update payment with gateway response
            $payment->gateway_transaction_id = $gatewayResponse['transaction_id'] ?? null;
            $payment->gateway_response = $gatewayResponse;
            
            // Map gateway status to our status
            $gatewayStatus = $gatewayResponse['status'] ?? 'failed';
            $payment->status = match ($gatewayStatus) {
                'completed' => 'completed',
                'processing' => 'processing',
                'pending' => 'pending',
                default => 'failed',
            };
            
            $payment->processed_at = now();

            if ($payment->status === 'completed') {
                // Update invoice status
                $invoice->status = 'paid';
                $invoice->paid_at = now();
                $invoice->save();
                \Modules\PaymentGateway\Events\InvoicePaid::dispatch($invoice);
            } elseif ($payment->status === 'pending' && isset($gatewayResponse['approval_url'])) {
                // PayPal requires user approval - store approval URL
                $payment->metadata = array_merge($payment->metadata ?? [], [
                    'approval_url' => $gatewayResponse['approval_url'],
                ]);
            }
        } catch (\Modules\PaymentGateway\Exceptions\PaymentGatewayException $e) {
            $payment->status = 'failed';
            $payment->failure_reason = $e->getMessage();
            $payment->gateway_response = array_merge($payment->gateway_response ?? [], [
                'error' => $e->getMessage(),
                'gateway_response' => $e->getGatewayResponse(),
            ]);
            \Log::error('Payment processing failed', [
                'payment_id' => $payment->id,
                'gateway' => $gateway->name,
                'error' => $e->getMessage(),
                'gateway_response' => $e->getGatewayResponse(),
            ]);
        } catch (\Exception $e) {
            $payment->status = 'failed';
            $payment->failure_reason = $e->getMessage();
            \Log::error('Unexpected payment processing error', [
                'payment_id' => $payment->id,
                'gateway' => $gateway->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $payment->save();

        return $payment;
    }

    /**
     * Generate a unique payment number
     */
    private function generatePaymentNumber(): string
    {
        $prefix = 'PAY-';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . $date . '-' . $random;
    }
}
