<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\EmailManagement\Services\EmailService;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\UserManagement\Models\User;

class DeliverAnnouncementEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $notificationId,
        public readonly int $userId,
    ) {
    }

    public function handle(EmailService $emailService): void
    {
        $notification = Notification::query()->find($this->notificationId);
        $user = User::query()->find($this->userId);

        if (!$notification || !$user) {
            Log::warning('DeliverAnnouncementEmailJob missing notification or user', [
                'notification_id' => $this->notificationId,
                'user_id' => $this->userId,
            ]);
            return;
        }

        $recipient = NotificationRecipient::query()
            ->where('notification_id', $notification->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$recipient) {
            Log::warning('DeliverAnnouncementEmailJob missing recipient row', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ]);
            return;
        }

        if ($recipient->delivered_email_at) {
            return;
        }

        try {
            // Prefer templates (can be added/managed in EmailManagement).
            $emailService->sendWithTemplate(
                to: $user->email,
                templateSlug: 'admin-announcement',
                variables: [
                    'name' => $user->name,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'severity' => $notification->severity,
                ],
                useQueue: true
            );
        } catch (\Throwable $e) {
            Log::warning('Announcement template send failed; falling back to sendNotification', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $subject = $notification->title;
            $body = '<h2>' . e($notification->title) . '</h2>'
                . '<p>' . nl2br(e($notification->body)) . '</p>';

            $emailService->sendNotification(
                to: $user->email,
                subject: $subject,
                body: $body,
                useQueue: true
            );
        }

        $recipient->forceFill(['delivered_email_at' => now()])->save();
    }
}

