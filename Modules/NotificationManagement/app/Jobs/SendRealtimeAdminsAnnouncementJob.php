<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Services\PusherService;

class SendRealtimeAdminsAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public readonly int $notificationId)
    {
    }

    public function handle(PusherService $pusher): void
    {
        $notification = Notification::query()->find($this->notificationId);

        if (!$notification) {
            return;
        }

        try {
            $pusher->sendAdminsAnnouncementCreated($notification);
        } catch (\Throwable $e) {
            Log::error('Realtime admin announcement send failed', [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

