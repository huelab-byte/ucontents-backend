<?php

declare(strict_types=1);

namespace Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Set Password Notification for new users created by admin
 */
class SetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The password set token.
     */
    public string $token;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
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
            $setPasswordUrl = $this->setPasswordUrl($notifiable);
            $appName = config('app.name', 'Our Application');

            Log::info('Preparing set password email for new user', [
                'email' => $notifiable->getEmailForPasswordReset(),
                'mailer' => config('mail.default'),
            ]);

            return (new MailMessage)
                ->subject("Welcome to {$appName} - Set Your Password")
                ->greeting("Hello {$notifiable->name}!")
                ->line("An account has been created for you on {$appName}.")
                ->line('Please click the button below to set your password and activate your account.')
                ->action('Set Password', $setPasswordUrl)
                ->line('This link will expire in ' . config('auth.passwords.users.expire', 60) . ' minutes.')
                ->line('If you did not expect this email, please ignore it or contact support.');
        } catch (\Throwable $e) {
            Log::error('Error creating set password mail message', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the set password URL for the given notifiable.
     */
    protected function setPasswordUrl($notifiable): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $token = $this->token;
        $email = $notifiable->getEmailForPasswordReset();

        // Use the same reset-password page - it can handle both reset and initial setup
        return "{$frontendUrl}/auth/set-password?token={$token}&email=" . urlencode($email);
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
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Set password notification failed to send', [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
