<?php

declare(strict_types=1);

namespace Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Magic Link Notification
 */
class MagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The magic link token.
     */
    public string $token;

    /**
     * The email address.
     */
    public string $email;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        try {
            $magicLinkUrl = $this->magicLinkUrl($notifiable);
            
            // Use getEmailForPasswordReset() like password reset notification does
            // This method works properly with queued notifications
            $email = method_exists($notifiable, 'getEmailForPasswordReset') 
                ? $notifiable->getEmailForPasswordReset() 
                : ($notifiable->email ?? $this->email);

            Log::info('Preparing magic link email', [
                'email' => $email,
                'mailer' => config('mail.default'),
                'queue' => config('queue.default'),
            ]);

            // Get token expiry from database settings with fallback to config
            $settingsService = app(\Modules\Authentication\Services\AuthenticationSettingsService::class);
            $expiryMinutes = $settingsService->get('features.magic_link.token_expiry', 15);

            return (new MailMessage)
                ->subject('Sign in to your account')
                ->line('Click the button below to sign in to your account. This link will expire in ' . $expiryMinutes . ' minutes.')
                ->action('Sign In', $magicLinkUrl)
                ->line('If you did not request this magic link, you can safely ignore this email.')
                ->line('This link can only be used once and will expire after ' . $expiryMinutes . ' minutes.');
        } catch (\Throwable $e) {
            Log::error('Error creating magic link mail message', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the magic link URL.
     */
    protected function magicLinkUrl($notifiable): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $token = $this->token;
        
        // Use getEmailForPasswordReset() like password reset notification does
        $email = method_exists($notifiable, 'getEmailForPasswordReset') 
            ? $notifiable->getEmailForPasswordReset() 
            : ($notifiable->email ?? $this->email);
        
        $email = urlencode($email);

        return "{$frontendUrl}/auth/magic-link/verify?token={$token}&email={$email}";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
        ];
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Magic link notification failed to send', [
            'exception' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'mailer' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_username' => config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET',
            'queue_connection' => config('queue.default'),
            'email' => $this->email,
        ]);
    }
}
