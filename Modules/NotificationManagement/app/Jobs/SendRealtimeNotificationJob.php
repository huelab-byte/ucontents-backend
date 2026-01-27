<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\NotificationManagement\Services\PusherService;

class SendRealtimeNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public readonly int $notificationId,
        public readonly int $userId,
    ) {
    }

    public function handle(PusherService $pusher): void
    {
        $recipient = NotificationRecipient::query()
            ->with(['notification'])
            ->where('notification_id', $this->notificationId)
            ->where('user_id', $this->userId)
            ->first();

        if (!$recipient) {
            return;
        }

        try {
            $pusher->sendNotificationCreated($recipient);
        } catch (\Throwable $e) {
            Log::error('Realtime notification send failed', [
                'notification_id' => $this->notificationId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

