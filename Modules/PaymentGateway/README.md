# PaymentGateway Module

Complete payment gateway integration module supporting Stripe and PayPal with invoice management, subscription handling, and refund processing.

## Features

- ✅ **Gateway Configuration**: Configure Stripe and PayPal with test/live mode support
- ✅ **Payment Processing**: Process one-time payments through Stripe or PayPal
- ✅ **Invoice Management**: Generate and manage invoices with admin editing capabilities
- ✅ **Subscription Management**: Create and manage recurring subscriptions (weekly, monthly, yearly)
- ✅ **Refund Processing**: Process full or partial refunds
- ✅ **Webhook Support**: Handle webhook events from Stripe and PayPal
- ✅ **Error Handling**: Comprehensive error handling with gateway-specific exceptions

## Installation

The module is already installed. Ensure you have the required packages:

```bash
composer require stripe/stripe-php
composer require paypal/rest-api-sdk-php
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Stripe
STRIPE_WEBHOOK_SECRET=whsec_...

# PayPal
PAYPAL_WEBHOOK_ID=...
```

### Gateway Configuration

Configure gateways via the admin API:

**Stripe:**
```json
POST /api/v1/admin/payment-gateways
{
  "name": "stripe",
  "display_name": "Stripe",
  "is_active": true,
  "is_test_mode": true,
  "credentials": {
    "test_secret_key": "sk_test_...",
    "live_secret_key": "sk_live_..."
  }
}
```

**PayPal:**
```json
POST /api/v1/admin/payment-gateways
{
  "name": "paypal",
  "display_name": "PayPal",
  "is_active": true,
  "is_test_mode": true,
  "credentials": {
    "test_client_id": "...",
    "test_client_secret": "...",
    "live_client_id": "...",
    "live_client_secret": "..."
  }
}
```

## API Endpoints

### Admin Endpoints

- `GET /api/v1/admin/payment-gateways` - List all gateways
- `POST /api/v1/admin/payment-gateways` - Configure a gateway
- `GET /api/v1/admin/payment-gateways/{id}` - Get gateway details
- `PUT /api/v1/admin/payment-gateways/{id}` - Update gateway
- `DELETE /api/v1/admin/payment-gateways/{id}` - Delete gateway

- `GET /api/v1/admin/invoices` - List all invoices
- `POST /api/v1/admin/invoices` - Create invoice
- `GET /api/v1/admin/invoices/{id}` - Get invoice
- `PUT /api/v1/admin/invoices/{id}` - Update invoice
- `DELETE /api/v1/admin/invoices/{id}` - Delete invoice

- `GET /api/v1/admin/payments` - List all payments
- `GET /api/v1/admin/payments/{id}` - Get payment details

- `GET /api/v1/admin/subscriptions` - List all subscriptions
- `GET /api/v1/admin/subscriptions/{id}` - Get subscription details

- `GET /api/v1/admin/refunds` - List all refunds
- `POST /api/v1/admin/refunds` - Process refund
- `GET /api/v1/admin/refunds/{id}` - Get refund details

### Customer Endpoints

- `GET /api/v1/customer/invoices` - List own invoices
- `GET /api/v1/customer/invoices/{id}` - Get own invoice

- `GET /api/v1/customer/payments` - List own payments
- `POST /api/v1/customer/payments` - Process payment
- `GET /api/v1/customer/payments/{id}` - Get own payment
- `POST /api/v1/customer/payments/{id}/execute-paypal` - Execute PayPal payment (after approval)

- `GET /api/v1/customer/subscriptions` - List own subscriptions
- `POST /api/v1/customer/subscriptions` - Create subscription
- `GET /api/v1/customer/subscriptions/{id}` - Get own subscription

- `GET /api/v1/customer/refunds` - List own refunds
- `POST /api/v1/customer/refunds` - Request refund
- `GET /api/v1/customer/refunds/{id}` - Get own refund

### Webhook Endpoints

- `POST /api/v1/webhooks/stripe` - Stripe webhook handler
- `POST /api/v1/webhooks/paypal` - PayPal webhook handler

## Usage Examples

### Creating an Invoice

```php
POST /api/v1/admin/invoices
{
  "user_id": 1,
  "type": "package",
  "subtotal": 100.00,
  "tax": 10.00,
  "discount": 0.00,
  "currency": "USD",
  "due_date": "2024-12-31"
}
```

### Processing a Payment (Stripe)

```php
POST /api/v1/customer/payments
{
  "invoice_id": 1,
  "gateway_name": "stripe",
  "payment_method": "card",
  "gateway_data": {
    "payment_method_id": "pm_...",
    "customer_email": "user@example.com"
  }
}
```

### Processing a Payment (PayPal)

```php
// Step 1: Create payment
POST /api/v1/customer/payments
{
  "invoice_id": 1,
  "gateway_name": "paypal",
  "gateway_data": {
    "return_url": "https://yourapp.com/payment/success",
    "cancel_url": "https://yourapp.com/payment/cancel"
  }
}

// Response includes approval_url - redirect user to this URL
// After user approves, execute payment:

// Step 2: Execute payment
POST /api/v1/customer/payments/{payment_id}/execute-paypal
{
  "payer_id": "PAYER_ID_FROM_PAYPAL"
}
```

### Creating a Subscription

```php
POST /api/v1/customer/subscriptions
{
  "user_id": 1,
  "name": "Premium Plan",
  "interval": "monthly",
  "amount": 29.99,
  "currency": "USD",
  "gateway_data": {
    "customer_id": "cus_...",
    "payment_method_id": "pm_..."
  }
}
```

### Processing a Refund

```php
POST /api/v1/admin/refunds
{
  "payment_id": 1,
  "amount": 50.00,
  "reason": "Customer requested refund"
}
```

## Webhook Setup

### Stripe Webhooks

1. Go to Stripe Dashboard → Developers → Webhooks
2. Add endpoint: `https://yourapp.com/api/v1/webhooks/stripe`
3. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
4. Copy webhook signing secret to `STRIPE_WEBHOOK_SECRET` in `.env`

### PayPal Webhooks

1. Go to PayPal Developer Dashboard → My Apps & Credentials
2. Select your app → Webhooks
3. Add webhook URL: `https://yourapp.com/api/v1/webhooks/paypal`
4. Select events:
   - `PAYMENT.SALE.COMPLETED`
   - `PAYMENT.SALE.DENIED`
   - `PAYMENT.CAPTURE.COMPLETED`
   - `PAYMENT.CAPTURE.REFUNDED`
5. Copy webhook ID to `PAYPAL_WEBHOOK_ID` in `.env`

## Error Handling

The module includes comprehensive error handling:

- `PaymentGatewayException` - Base exception for all gateway errors
- `PaymentProcessingException` - Payment processing failures
- `RefundProcessingException` - Refund processing failures
- `SubscriptionProcessingException` - Subscription processing failures
- `GatewayConfigurationException` - Invalid gateway configuration

All exceptions include gateway response data for debugging.

## Database Schema

- `payment_gateways` - Gateway configurations
- `invoices` - Invoice records
- `payments` - Payment transactions
- `subscriptions` - Subscription records
- `refunds` - Refund records

## Security

- All admin endpoints require authentication and admin role
- Customer endpoints require authentication
- Webhook endpoints verify signatures
- Gateway credentials are stored encrypted (implement encryption in production)
- Rate limiting on webhook endpoints

## Notes

- PayPal subscriptions require billing plan setup (not fully implemented)
- Gateway credentials should be encrypted before storage
- Webhook verification for PayPal needs enhancement for production
- Consider implementing payment method storage for recurring payments
