# PlanManagement Module

Admin-defined subscription plans with configurable limits, integrated with PaymentGateway for invoices and recurring payments.

## Features

- **Plans**: AI usage limit, max file upload, total storage, features (JSON), max connections (social channels), monthly post limit, subscription type (weekly, monthly, yearly, lifetime), price, currency.
- **Admin CRUD**: Full plan management at `/api/v1/admin/plans`.
- **Public list**: `GET /api/v1/public/plans` (no auth) for marketing/checkout.
- **Customer subscribe**: `POST /api/v1/customer/plans/{plan}/subscribe` (auth required). Recurring plans create a gateway subscription; lifetime plans create an invoice + pending subscription (activated when invoice is paid).
- **Admin notification**: When a new subscription is created, all admins receive an in-app notification.
- **Customer expiry notification**: Daily job notifies customers whose subscription expires or renews within N days (config: `subscription_expiring_days`, default 7).

## Setup (and deployment)

Use the standard Artisan commands (same as for deployment):

1. **Autoload:** `composer dump-autoload` (run after clone/pull if the module is not found; the deploy script runs this).
2. **Migrations:** `php artisan migrate` (or `php artisan migrate --force` in production). This runs all pending migrations, including PlanManagement’s `2026_01_29_100000_create_plans_table`.
3. **Seeders:** `php artisan db:seed` (or `php artisan db:seed --force` in production). `DatabaseSeeder` calls `PlanManagementDatabaseSeeder`, which creates PlanManagement permissions and assigns them to admin/customer roles. Safe to run on existing DB (permissions use firstOrCreate; role sync is additive).
4. **Scheduler:** Ensure `php artisan schedule:run` runs daily (e.g. cron) so `NotifySubscriptionExpiringJob` runs.

## Integration

- **PaymentGateway**: Uses `Subscription` (subscriptionable = Plan), `Invoice` (invoiceable = Plan), `CreateSubscriptionAction`, `GenerateInvoiceAction`, `ProcessPaymentAction`. Listens for `InvoicePaid` to activate lifetime subscriptions.
- **NotificationManagement**: Creates notifications and recipients; uses `SendRealtimeNotificationJob` for admin and customer notifications.

## Config

- `config('planmanagement.subscription_expiring_days')` (default 7). Override with env `PLAN_SUBSCRIPTION_EXPIRING_DAYS`.
