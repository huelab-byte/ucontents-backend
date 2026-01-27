<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Services;

use Modules\PaymentGateway\Exceptions\GatewayConfigurationException;
use Modules\PaymentGateway\Exceptions\PaymentProcessingException;
use Modules\PaymentGateway\Exceptions\RefundProcessingException;
use Modules\PaymentGateway\Exceptions\SubscriptionProcessingException;
use Modules\PaymentGateway\Models\PaymentGateway;
use Modules\PaymentGateway\Services\Gateways\GatewayInterface;
use Modules\PaymentGateway\Services\Gateways\PayPalService;
use Modules\PaymentGateway\Services\Gateways\StripeService;

/**
 * Service for payment gateway operations
 * 
 * This service handles communication with payment gateways (Stripe, PayPal, etc.)
 */
class PaymentGatewayService
{
    /**
     * Get gateway service instance
     */
    private function getGatewayService(PaymentGateway $gateway): GatewayInterface
    {
        $credentials = $gateway->credentials ?? [];
        
        if (empty($credentials)) {
            throw new GatewayConfigurationException(
                "Gateway '{$gateway->name}' is not properly configured. Missing credentials."
            );
        }

        return match ($gateway->name) {
            'stripe' => $this->createStripeService($credentials, $gateway->is_test_mode),
            'paypal' => $this->createPayPalService($credentials, $gateway->is_test_mode),
            default => throw new GatewayConfigurationException(
                "Unsupported gateway: {$gateway->name}"
            ),
        };
    }

    /**
     * Create Stripe service instance
     */
    private function createStripeService(array $credentials, bool $isTestMode): StripeService
    {
        $apiKey = $isTestMode 
            ? ($credentials['test_secret_key'] ?? $credentials['secret_key'] ?? null)
            : ($credentials['live_secret_key'] ?? $credentials['secret_key'] ?? null);

        if (!$apiKey) {
            throw new GatewayConfigurationException(
                'Stripe API key is required. Please configure secret_key or test_secret_key/live_secret_key.'
            );
        }

        return new StripeService($apiKey);
    }

    /**
     * Create PayPal service instance
     */
    private function createPayPalService(array $credentials, bool $isTestMode): PayPalService
    {
        $clientId = $isTestMode
            ? ($credentials['test_client_id'] ?? $credentials['client_id'] ?? null)
            : ($credentials['live_client_id'] ?? $credentials['client_id'] ?? null);

        $clientSecret = $isTestMode
            ? ($credentials['test_client_secret'] ?? $credentials['client_secret'] ?? null)
            : ($credentials['live_client_secret'] ?? $credentials['client_secret'] ?? null);

        if (!$clientId || !$clientSecret) {
            throw new GatewayConfigurationException(
                'PayPal Client ID and Client Secret are required. Please configure client_id and client_secret.'
            );
        }

        return new PayPalService($clientId, $clientSecret, $isTestMode);
    }

    /**
     * Process a payment through the gateway
     */
    public function processPayment(PaymentGateway $gateway, array $paymentData): array
    {
        try {
            $gatewayService = $this->getGatewayService($gateway);
            
            // Add gateway-specific data
            $paymentData['gateway'] = $gateway->name;
            $paymentData['is_test_mode'] = $gateway->is_test_mode;

            return $gatewayService->processPayment($paymentData);
        } catch (GatewayConfigurationException $e) {
            throw $e;
        } catch (PaymentProcessingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PaymentProcessingException(
                'Payment processing failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Create a subscription in the gateway
     */
    public function createSubscription(PaymentGateway $gateway, array $subscriptionData): array
    {
        try {
            $gatewayService = $this->getGatewayService($gateway);
            
            // Add gateway-specific data
            $subscriptionData['gateway'] = $gateway->name;
            $subscriptionData['is_test_mode'] = $gateway->is_test_mode;

            return $gatewayService->createSubscription($subscriptionData);
        } catch (GatewayConfigurationException $e) {
            throw $e;
        } catch (SubscriptionProcessingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new SubscriptionProcessingException(
                'Subscription creation failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Process a refund through the gateway
     */
    public function processRefund(PaymentGateway $gateway, array $refundData): array
    {
        try {
            $gatewayService = $this->getGatewayService($gateway);
            
            // Add gateway-specific data
            $refundData['gateway'] = $gateway->name;
            $refundData['is_test_mode'] = $gateway->is_test_mode;

            return $gatewayService->processRefund($refundData);
        } catch (GatewayConfigurationException $e) {
            throw $e;
        } catch (RefundProcessingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RefundProcessingException(
                'Refund processing failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(PaymentGateway $gateway, string $subscriptionId): bool
    {
        try {
            $gatewayService = $this->getGatewayService($gateway);
            return $gatewayService->cancelSubscription($subscriptionId);
        } catch (\Exception $e) {
            throw new SubscriptionProcessingException(
                'Subscription cancellation failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(PaymentGateway $gateway, string $payload, string $signature): bool
    {
        try {
            $gatewayService = $this->getGatewayService($gateway);
            return $gatewayService->verifyWebhook($payload, $signature);
        } catch (\Exception $e) {
            \Log::error('Webhook verification failed', [
                'gateway' => $gateway->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
