<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\EmailManagement\DTOs\SendEmailDTO;
use Modules\EmailManagement\Jobs\SendEmailJob;
use Modules\EmailManagement\Models\EmailLog;
use Modules\EmailManagement\Models\EmailTemplate;
use Modules\EmailManagement\Models\SmtpConfiguration;

/**
 * Email Service
 * 
 * Handles sending emails with queue support and SMTP configuration
 */
class EmailService
{
    /**
     * Get site name from General Settings with fallback to config
     */
    private function getSiteName(): string
    {
        try {
            // Try to get from GeneralSettings module
            if (class_exists(\Modules\GeneralSettings\Services\GeneralSettingsService::class)) {
                $settingsService = app(\Modules\GeneralSettings\Services\GeneralSettingsService::class);
                $siteName = $settingsService->get('branding.site_name');
                if (!empty($siteName)) {
                    return $siteName;
                }
            }
        } catch (\Exception $e) {
            // Fallback to config if GeneralSettings module is not available
            Log::debug('Could not get site name from GeneralSettings', ['error' => $e->getMessage()]);
        }
        
        return config('app.name', 'uContents');
    }

    /**
     * Get site logo URL from General Settings with fallback
     */
    private function getSiteLogo(): string
    {
        try {
            // Try to get from GeneralSettings module
            if (class_exists(\Modules\GeneralSettings\Services\GeneralSettingsService::class)) {
                $settingsService = app(\Modules\GeneralSettings\Services\GeneralSettingsService::class);
                $logo = $settingsService->get('branding.logo');
                if (!empty($logo)) {
                    // If logo is already a full URL, return it
                    if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
                        return $logo;
                    }
                    // Otherwise, construct full URL from relative path
                    $appUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
                    $normalizedPath = str_starts_with($logo, '/') ? $logo : '/' . $logo;
                    return $appUrl . $normalizedPath;
                }
            }
        } catch (\Exception $e) {
            // Fallback if GeneralSettings module is not available
            Log::debug('Could not get site logo from GeneralSettings', ['error' => $e->getMessage()]);
        }
        
        // Return empty string if no logo is configured
        return '';
    }

    /**
     * Send email (with optional queue support)
     */
    public function send(SendEmailDTO $dto): EmailLog
    {
        // Create email log entry
        $emailLog = EmailLog::create([
            'smtp_configuration_id' => $dto->smtpConfigurationId,
            'email_template_id' => $dto->templateId,
            'to' => $dto->to,
            'cc' => $dto->cc,
            'bcc' => $dto->bcc,
            'subject' => $dto->subject,
            'body' => $dto->body,
            'status' => 'pending',
            'metadata' => $dto->metadata,
        ]);

        Log::info('Email log created', [
            'email_log_id' => $emailLog->id,
            'to' => $emailLog->to,
            'subject' => $emailLog->subject,
            'use_queue' => $dto->useQueue,
            'template_id' => $emailLog->email_template_id,
        ]);

        // If queue is enabled, dispatch job
        if ($dto->useQueue) {
            $job = SendEmailJob::dispatch($emailLog->id);
            
            // Only specify queue if configured (null/empty = use default queue)
            $queueName = config('emailmanagement.default_queue');
            if (!empty($queueName) && $queueName !== 'default') {
                $job->onQueue($queueName);
            }
            
            Log::info('Email job dispatched to queue', [
                'email_log_id' => $emailLog->id,
                'queue' => $queueName ?: 'default',
            ]);
        } else {
            // Send immediately
            Log::info('Sending email immediately (no queue)', [
                'email_log_id' => $emailLog->id,
            ]);
            $this->sendEmail($emailLog);
        }

        return $emailLog;
    }

    /**
     * Send email using template
     */
    public function sendWithTemplate(
        string $to,
        string $templateSlug,
        array $variables = [],
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        $template = EmailTemplate::findBySlug($templateSlug);

        if (!$template) {
            Log::error("Email template '{$templateSlug}' not found", [
                'to' => $to,
                'template_slug' => $templateSlug,
            ]);
            throw new \Exception("Email template '{$templateSlug}' not found.");
        }

        if (!$template->is_active) {
            Log::warning("Email template '{$templateSlug}' is not active", [
                'to' => $to,
                'template_slug' => $templateSlug,
            ]);
            throw new \Exception("Email template '{$templateSlug}' is not active.");
        }

        // Automatically include site branding variables (can be overridden by passed variables)
        $allVariables = array_merge([
            'site_name' => $this->getSiteName(),
            'site_logo' => $this->getSiteLogo(),
            'app_name' => $this->getSiteName(), // Keep app_name for backward compatibility
        ], $variables);

        $rendered = $template->render($allVariables);
        
        Log::info('Rendering email template', [
            'template_slug' => $templateSlug,
            'template_id' => $template->id,
            'to' => $to,
            'variables' => array_keys($variables),
        ]);

        $dto = new SendEmailDTO(
            to: $to,
            cc: null,
            bcc: null,
            subject: $rendered['subject'],
            body: $rendered['body_html'],
            templateId: $template->id,
            templateVariables: $variables,
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
            metadata: null,
        );

        return $this->send($dto);
    }

    /**
     * Send email notification (convenience method)
     */
    public function sendNotification(
        string $to,
        string $subject,
        string $body,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        $dto = new SendEmailDTO(
            to: $to,
            cc: null,
            bcc: null,
            subject: $subject,
            body: $body,
            templateId: null,
            templateVariables: null,
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
            metadata: null,
        );

        return $this->send($dto);
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(
        string $to,
        string $name,
        string $loginUrl,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        return $this->sendWithTemplate(
            to: $to,
            templateSlug: 'welcome-email',
            variables: [
                'name' => $name,
                'app_name' => $this->getSiteName(),
                'login_url' => $loginUrl,
            ],
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
        );
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(
        string $to,
        string $name,
        string $resetUrl,
        int $expiresInMinutes = 60,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        return $this->sendWithTemplate(
            to: $to,
            templateSlug: 'password-reset',
            variables: [
                'name' => $name,
                'reset_url' => $resetUrl,
                'expires_in' => $expiresInMinutes,
                'app_name' => $this->getSiteName(),
            ],
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
        );
    }

    /**
     * Send magic link email
     */
    public function sendMagicLinkEmail(
        string $to,
        string $name,
        string $magicLink,
        int $expiresInMinutes = 15,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        return $this->sendWithTemplate(
            to: $to,
            templateSlug: 'magic-link',
            variables: [
                'name' => $name,
                'magic_link' => $magicLink,
                'expires_in' => $expiresInMinutes,
                'app_name' => $this->getSiteName(),
            ],
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
        );
    }

    /**
     * Send OTP code email
     */
    public function sendOtpEmail(
        string $to,
        string $name,
        string $otpCode,
        int $expiresInMinutes = 10,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        return $this->sendWithTemplate(
            to: $to,
            templateSlug: 'otp-code',
            variables: [
                'name' => $name,
                'otp_code' => $otpCode,
                'expires_in' => $expiresInMinutes,
                'app_name' => $this->getSiteName(),
            ],
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
        );
    }

    /**
     * Send email verification email
     */
    public function sendEmailVerificationEmail(
        string $to,
        string $name,
        string $verificationUrl,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        return $this->sendWithTemplate(
            to: $to,
            templateSlug: 'email-verification',
            variables: [
                'name' => $name,
                'verification_url' => $verificationUrl,
                'app_name' => $this->getSiteName(),
            ],
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
        );
    }

    /**
     * Send password changed notification
     */
    public function sendPasswordChangedEmail(
        string $to,
        string $name,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        return $this->sendWithTemplate(
            to: $to,
            templateSlug: 'password-changed',
            variables: [
                'name' => $name,
                'changed_at' => now()->format('F j, Y \a\t g:i A'),
                'app_name' => $this->getSiteName(),
                'support_url' => config('app.support_url', '#'),
            ],
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
        );
    }

    /**
     * Send account activated email
     */
    public function sendAccountActivatedEmail(
        string $to,
        string $name,
        string $loginUrl,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        return $this->sendWithTemplate(
            to: $to,
            templateSlug: 'account-activated',
            variables: [
                'name' => $name,
                'login_url' => $loginUrl,
                'app_name' => $this->getSiteName(),
            ],
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
        );
    }

    /**
     * Send set password email (for new users created by admin)
     */
    public function sendSetPasswordEmail(
        string $to,
        string $name,
        string $setPasswordUrl,
        int $expiresInMinutes = 60,
        ?int $smtpConfigurationId = null,
        bool $useQueue = true
    ): EmailLog {
        return $this->sendWithTemplate(
            to: $to,
            templateSlug: 'set-password',
            variables: [
                'name' => $name,
                'set_password_url' => $setPasswordUrl,
                'expires_in' => $expiresInMinutes,
                'app_name' => $this->getSiteName(),
            ],
            smtpConfigurationId: $smtpConfigurationId,
            useQueue: $useQueue,
        );
    }

    /**
     * Actually send the email (called by job or directly)
     */
    public function sendEmail(EmailLog $emailLog): void
    {
        try {
            // Get SMTP configuration
            $smtpConfig = $this->getSmtpConfiguration($emailLog->smtp_configuration_id);

            // Configure mail settings before sending if SMTP config exists
            if ($smtpConfig) {
                $this->configureMailForSending($smtpConfig);
                $mailer = 'smtp';
                $fromAddress = $smtpConfig->from_address;
                $fromName = $smtpConfig->from_name ?? $this->getSiteName();
            } else {
                // No SMTP config - use default mailer from config (will use .env MAIL_* settings)
                // Ensure we're using the 'smtp' mailer which reads from .env
                $defaultMailer = config('mail.default', 'smtp');
                
                // If default is 'log', switch to 'smtp' to actually send emails
                // (unless explicitly set to 'log' for testing)
                $mailer = ($defaultMailer === 'log') ? 'smtp' : $defaultMailer;
                
                $fromAddress = config('mail.from.address', 'hello@example.com');
                $fromName = config('mail.from.name', $this->getSiteName());
                
                Log::info('No SMTP configuration found, using default mailer from .env', [
                    'email_log_id' => $emailLog->id,
                    'mailer' => $mailer,
                    'from' => $fromAddress,
                    'mail_host' => config('mail.mailers.smtp.host'),
                    'mail_port' => config('mail.mailers.smtp.port'),
                ]);
            }

            // Send email (Mail::html() sends immediately)
            Mail::mailer($mailer)->html($emailLog->body, function ($message) use ($emailLog, $fromAddress, $fromName) {
                $message->to($emailLog->to)
                    ->subject($emailLog->subject)
                    ->from($fromAddress, $fromName);

                if ($emailLog->cc) {
                    $message->cc($emailLog->cc);
                }

                if ($emailLog->bcc) {
                    $message->bcc($emailLog->bcc);
                }
            });

            $emailLog->markAsSent();

            Log::info('Email sent successfully', [
                'email_log_id' => $emailLog->id,
                'to' => $emailLog->to,
                'mailer' => $mailer,
                'has_smtp_config' => $smtpConfig !== null,
                'from' => $fromAddress,
            ]);
        } catch (\Exception $e) {
            $emailLog->markAsFailed($e->getMessage());

            Log::error('Failed to send email', [
                'email_log_id' => $emailLog->id,
                'to' => $emailLog->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get SMTP configuration (default or specified)
     */
    private function getSmtpConfiguration(?int $smtpConfigurationId = null): ?SmtpConfiguration
    {
        if ($smtpConfigurationId) {
            $config = SmtpConfiguration::find($smtpConfigurationId);
            if ($config && $config->is_active) {
                return $config;
            }
        }

        // Get default active SMTP configuration
        $default = SmtpConfiguration::where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($default) {
            return $default;
        }

        // Fallback: get any active SMTP configuration
        return SmtpConfiguration::where('is_active', true)->first();
    }

    /**
     * Configure mail settings from SMTP configuration
     * This updates the default SMTP mailer configuration
     */
    private function configureMailForSending(SmtpConfiguration $smtpConfig): void
    {
        if (!$smtpConfig->is_active) {
            throw new \Exception("SMTP configuration '{$smtpConfig->name}' is not active.");
        }

        $config = $smtpConfig->toMailConfig();

        // Validate required SMTP settings
        if (empty($config['host']) || empty($config['username']) || empty($config['password'])) {
            throw new \Exception("SMTP configuration '{$smtpConfig->name}' is missing required settings (host, username, or password).");
        }

        // Get the base SMTP config to preserve other settings
        $baseSmtpConfig = config('mail.mailers.smtp', []);

        // Update the SMTP mailer configuration with database settings
        Config::set('mail.mailers.smtp', array_merge($baseSmtpConfig, [
            'transport' => 'smtp',
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'] ?? null,
            'username' => $config['username'],
            'password' => $config['password'],
            'timeout' => $baseSmtpConfig['timeout'] ?? null,
            'local_domain' => $baseSmtpConfig['local_domain'] ?? parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST),
        ]));

        // Set global from address
        Config::set('mail.from', [
            'address' => $config['from']['address'],
            'name' => $config['from']['name'],
        ]);

        // Ensure default mailer is set to smtp when using SMTP config
        Config::set('mail.default', 'smtp');

        Log::debug('SMTP configuration applied', [
            'smtp_config_id' => $smtpConfig->id,
            'host' => $config['host'],
            'port' => $config['port'],
            'from' => $config['from']['address'],
        ]);
    }

    /**
     * Test SMTP configuration by sending a test email
     */
    public function testSmtpConfiguration(?int $smtpConfigurationId = null, string $testEmail = 'test@example.com'): array
    {
        try {
            $smtpConfig = $this->getSmtpConfiguration($smtpConfigurationId);
            
            if ($smtpConfig) {
                $this->configureMailForSending($smtpConfig);
                $mailer = 'smtp';
                $fromAddress = $smtpConfig->from_address;
            } else {
                $mailer = config('mail.default', 'smtp');
                $fromAddress = config('mail.from.address', 'hello@example.com');
            }

            // Try to send a test email
            Mail::mailer($mailer)->html('<h1>Test Email</h1><p>This is a test email to verify SMTP configuration.</p>', function ($message) use ($testEmail, $fromAddress) {
                $message->to($testEmail)
                    ->subject('SMTP Configuration Test')
                    ->from($fromAddress);
            });

            return [
                'success' => true,
                'message' => 'SMTP configuration test successful. Email sent to ' . $testEmail,
                'mailer' => $mailer,
                'from' => $fromAddress,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SMTP configuration test failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
}
