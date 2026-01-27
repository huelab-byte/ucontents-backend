# Email Management Module

A comprehensive email management system with SMTP configuration, email templates, and queue support.

## Features

- **SMTP Configuration**: Manage multiple SMTP configurations with encryption support
- **Email Templates**: Create and manage reusable email templates with variable substitution
- **Queue Support**: Send emails asynchronously using Laravel queues
- **Email Logging**: Track all sent emails with status and error messages
- **Admin Interface**: Full admin interface for managing configurations and templates

## Usage

### Sending Emails

#### Using the Email Service

```php
use Modules\EmailManagement\Services\EmailService;
use Modules\EmailManagement\DTOs\SendEmailDTO;

$emailService = app(EmailService::class);

// Send a simple email
$dto = new SendEmailDTO(
    to: 'user@example.com',
    subject: 'Welcome!',
    body: '<h1>Welcome to our platform!</h1>',
    useQueue: true
);

$emailLog = $emailService->send($dto);
```

#### Send Email with Template

```php
// Send email using a template
$emailLog = $emailService->sendWithTemplate(
    to: 'user@example.com',
    templateSlug: 'welcome-email',
    variables: [
        'name' => 'John Doe',
        'activation_link' => 'https://example.com/activate/123'
    ],
    useQueue: true
);
```

#### Send Notification

```php
// Convenience method for notifications
$emailLog = $emailService->sendNotification(
    to: 'user@example.com',
    subject: 'New Message',
    body: '<p>You have a new message.</p>',
    useQueue: true
);
```

### Creating Email Templates

Templates support variable substitution using `{{variable_name}}` syntax:

**Template Subject:**
```
Welcome {{name}}!
```

**Template Body:**
```html
<h1>Hello {{name}}!</h1>
<p>Click here to activate your account: {{activation_link}}</p>
```

When sending with variables:
```php
$emailService->sendWithTemplate(
    to: 'user@example.com',
    templateSlug: 'welcome-email',
    variables: [
        'name' => 'John Doe',
        'activation_link' => 'https://example.com/activate/123'
    ]
);
```

### System Email Templates

The module comes with default system email templates that are automatically seeded:

- **welcome-email** - Welcome new users
- **password-reset** - Password reset requests
- **magic-link** - Magic link authentication
- **otp-code** - OTP verification codes
- **email-verification** - Email address verification
- **password-changed** - Password change confirmation
- **account-activated** - Account activation notification

#### Using System Email Templates

Convenience methods are available for all system emails:

```php
// Welcome email
$emailService->sendWelcomeEmail(
    to: 'user@example.com',
    name: 'John Doe',
    loginUrl: 'https://example.com/login'
);

// Password reset
$emailService->sendPasswordResetEmail(
    to: 'user@example.com',
    name: 'John Doe',
    resetUrl: 'https://example.com/reset?token=abc123',
    expiresInMinutes: 60
);

// Magic link
$emailService->sendMagicLinkEmail(
    to: 'user@example.com',
    name: 'John Doe',
    magicLink: 'https://example.com/magic?token=xyz789',
    expiresInMinutes: 15
);

// OTP code
$emailService->sendOtpEmail(
    to: 'user@example.com',
    name: 'John Doe',
    otpCode: '123456',
    expiresInMinutes: 10
);

// Email verification
$emailService->sendEmailVerificationEmail(
    to: 'user@example.com',
    name: 'John Doe',
    verificationUrl: 'https://example.com/verify?token=def456'
);

// Password changed notification
$emailService->sendPasswordChangedEmail(
    to: 'user@example.com',
    name: 'John Doe'
);

// Account activated
$emailService->sendAccountActivatedEmail(
    to: 'user@example.com',
    name: 'John Doe',
    loginUrl: 'https://example.com/login'
);
```

All templates can be customized through the admin interface at `/admin/settings/email`.

### SMTP Configuration

The module supports multiple SMTP configurations. Set one as default to use it automatically:

```php
use Modules\EmailManagement\Models\SmtpConfiguration;

$config = SmtpConfiguration::find(1);
$config->setAsDefault();
```

When sending emails without specifying an SMTP configuration, the default active configuration will be used.

## API Endpoints

### Admin Endpoints

All admin endpoints require authentication and admin role.

#### SMTP Configurations

- `GET /api/v1/admin/email-management/smtp-configurations` - List all SMTP configurations
- `GET /api/v1/admin/email-management/smtp-configurations/{id}` - Get specific configuration
- `POST /api/v1/admin/email-management/smtp-configurations` - Create new configuration
- `PUT /api/v1/admin/email-management/smtp-configurations/{id}` - Update configuration
- `DELETE /api/v1/admin/email-management/smtp-configurations/{id}` - Delete configuration
- `POST /api/v1/admin/email-management/smtp-configurations/{id}/set-default` - Set as default

#### Email Templates

- `GET /api/v1/admin/email-management/email-templates` - List all templates
- `GET /api/v1/admin/email-management/email-templates/{id}` - Get specific template
- `POST /api/v1/admin/email-management/email-templates` - Create new template
- `PUT /api/v1/admin/email-management/email-templates/{id}` - Update template
- `DELETE /api/v1/admin/email-management/email-templates/{id}` - Delete template

#### Test Email

- `POST /api/v1/admin/email-management/test-email` - Send test email

## Database Tables

- `smtp_configurations` - SMTP configuration settings
- `email_templates` - Email templates
- `email_logs` - Email sending logs

## Queue Configuration

Make sure your queue worker is running:

```bash
php artisan queue:work
```

The module uses the queue name configured in `config/emailmanagement.php` (default: `emails`).

## Security

- SMTP passwords are encrypted using Laravel's Crypt facade
- All admin endpoints require authentication and admin role
- Email logs are stored for auditing purposes

## Queue Worker Setup

### Default Behavior
By default, emails use the default queue connection. Simply run:
```bash
php artisan queue:work
```

### Using a Dedicated Queue
If you want to use a dedicated `emails` queue, set in `.env`:
```env
EMAIL_QUEUE=emails
```

Then run the queue worker with:
```bash
php artisan queue:work --queue=emails
```

### Production
Use a process manager like Supervisor to keep the queue worker running:
```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --tries=3 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=1
```

If using a dedicated emails queue, add `--queue=emails` to the command.

## Frontend

The admin interface is available at `/admin/settings/email` with:
- SMTP Configuration management
- Email Template editor
- Test email functionality
