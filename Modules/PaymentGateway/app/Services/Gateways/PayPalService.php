<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Services\Gateways;

use Modules\PaymentGateway\Exceptions\PaymentProcessingException;
use Modules\PaymentGateway\Exceptions\RefundProcessingException;
use Modules\PaymentGateway\Exceptions\SubscriptionProcessingException;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Plan;
use PayPal\Api\PlanList;
use PayPal\Api\Refund;
use PayPal\Api\RefundRequest;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

/**
 * PayPal payment gateway service
 */
class PayPalService implements GatewayInterface
{
    private ApiContext $apiContext;

    public function __construct(string $clientId, string $clientSecret, bool $isTestMode = true)
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential($clientId, $clientSecret)
        );

        $this->apiContext->setConfig([
            'mode' => $isTestMode ? 'sandbox' : 'live',
            'log.LogEnabled' => config('app.debug'),
            'log.FileName' => storage_path('logs/paypal.log'),
            'log.LogLevel' => 'DEBUG',
        ]);
    }

    /**
     * Process a payment
     */
    public function processPayment(array $paymentData): array
    {
        try {
            $amount = $paymentData['amount'];
            $currency = strtoupper($paymentData['currency'] ?? 'USD');
            $returnUrl = $paymentData['return_url'] ?? null;
            $cancelUrl = $paymentData['cancel_url'] ?? null;

            // Create payer
            $payer = new Payer();
            $payer->setPaymentMethod('paypal');

            // Create amount
            $amountObj = new Amount();
            $amountObj->setCurrency($currency)
                ->setTotal(number_format($amount, 2, '.', ''));

            // Create transaction
            $transaction = new Transaction();
            $transaction->setAmount($amountObj)
                ->setDescription($paymentData['description'] ?? 'Payment')
                ->setInvoiceNumber($paymentData['invoice_id'] ?? null);

            // Create redirect URLs
            $redirectUrls = new RedirectUrls();
            if ($returnUrl) {
                $redirectUrls->setReturnUrl($returnUrl);
            }
            if ($cancelUrl) {
                $redirectUrls->setCancelUrl($cancelUrl);
            }

            // Create payment
            $payment = new Payment();
            $payment->setIntent('sale')
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions([$transaction]);

            $payment->create($this->apiContext);

            // If approval URL exists, payment needs user approval
            $approvalUrl = null;
            foreach ($payment->getLinks() as $link) {
                if ($link->getRel() === 'approval_url') {
                    $approvalUrl = $link->getHref();
                    break;
                }
            }

            return [
                'transaction_id' => $payment->getId(),
                'status' => $approvalUrl ? 'pending' : 'completed',
                'approval_url' => $approvalUrl,
                'response' => [
                    'payment' => $payment->toArray(),
                ],
            ];
        } catch (PayPalConnectionException $e) {
            throw new PaymentProcessingException(
                'PayPal payment failed: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                ['paypal_error' => $e->getData()]
            );
        } catch (\Exception $e) {
            throw new PaymentProcessingException(
                'PayPal payment failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Execute a PayPal payment (after user approval)
     */
    public function executePayment(string $paymentId, string $payerId): array
    {
        try {
            $payment = Payment::get($paymentId, $this->apiContext);
            $execution = new PaymentExecution();
            $execution->setPayerId($payerId);

            $payment->execute($execution, $this->apiContext);

            return [
                'transaction_id' => $payment->getId(),
                'status' => 'completed',
                'response' => [
                    'payment' => $payment->toArray(),
                ],
            ];
        } catch (\Exception $e) {
            throw new PaymentProcessingException(
                'PayPal payment execution failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Create a subscription
     */
    public function createSubscription(array $subscriptionData): array
    {
        try {
            // PayPal subscriptions require creating a billing plan first
            // This is a simplified implementation
            // In production, you'd want to create and store plans
            
            $amount = $subscriptionData['amount'];
            $currency = strtoupper($subscriptionData['currency'] ?? 'USD');
            $interval = $subscriptionData['interval'] ?? 'monthly';

            // Note: PayPal subscription creation is more complex
            // This is a placeholder - you'd need to:
            // 1. Create a billing plan
            // 2. Activate the plan
            // 3. Create an agreement
            // 4. Get approval URL
            
            throw new SubscriptionProcessingException(
                'PayPal subscription creation requires billing plan setup. Please implement billing plan creation first.'
            );
        } catch (\Exception $e) {
            throw new SubscriptionProcessingException(
                'PayPal subscription creation failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Process a refund
     */
    public function processRefund(array $refundData): array
    {
        try {
            $saleId = $refundData['sale_id'] ?? null;
            $amount = $refundData['amount'] ?? null;
            $currency = strtoupper($refundData['currency'] ?? 'USD');

            if (!$saleId) {
                throw new RefundProcessingException('Sale ID is required for PayPal refunds');
            }

            $refundRequest = new RefundRequest();
            
            if ($amount !== null) {
                $refundAmount = new Amount();
                $refundAmount->setCurrency($currency)
                    ->setTotal(number_format($amount, 2, '.', ''));
                $refundRequest->setAmount($refundAmount);
            }

            $sale = new \PayPal\Api\Sale();
            $refundedSale = $sale->refundSale($saleId, $refundRequest, $this->apiContext);

            return [
                'refund_id' => $refundedSale->getId(),
                'status' => $this->mapPayPalRefundStatus($refundedSale->getState()),
                'response' => [
                    'refund' => $refundedSale->toArray(),
                ],
            ];
        } catch (\Exception $e) {
            throw new RefundProcessingException(
                'PayPal refund failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        try {
            // PayPal subscription cancellation implementation
            // This would require the billing agreement ID
            throw new SubscriptionProcessingException(
                'PayPal subscription cancellation requires billing agreement implementation'
            );
        } catch (\Exception $e) {
            throw new SubscriptionProcessingException(
                'PayPal subscription cancellation failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        // PayPal webhook verification requires additional setup
        // For now, return true if webhook secret is configured
        $webhookId = config('paymentgateway.paypal.webhook_id');
        return !empty($webhookId);
    }

    /**
     * Map PayPal refund status
     */
    private function mapPayPalRefundStatus(string $state): string
    {
        return match ($state) {
            'completed' => 'completed',
            'pending' => 'processing',
            'failed' => 'failed',
            default => 'pending',
        };
    }
}
