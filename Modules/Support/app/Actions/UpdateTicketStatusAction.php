<?php

declare(strict_types=1);

namespace Modules\Support\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\Jobs\SendRealtimeNotificationJob;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\NotificationManagement\Services\PusherService;
use Modules\Support\DTOs\UpdateTicketStatusDTO;
use Modules\Support\Models\SupportTicket;

class UpdateTicketStatusAction
{
    public function execute(SupportTicket $ticket, UpdateTicketStatusDTO $dto): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $dto) {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => $dto->status,
            ]);

            // Notify user if status changed
            if ($oldStatus !== $dto->status) {
                $this->notifyUserStatusChanged($ticket, $oldStatus, $dto->status);
                $this->broadcastStatusChange($ticket, $oldStatus, $dto->status);
            }

            Log::info('Support ticket status updated', [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $dto->status,
            ]);

            return $ticket->fresh();
        });
    }

    private function notifyUserStatusChanged(SupportTicket $ticket, string $oldStatus, string $newStatus): void
    {
        $notification = Notification::create([
            'type' => 'support_ticket_status_changed',
            'title' => "Ticket {$ticket->ticket_number} Status Updated",
            'body' => "Your ticket status has been changed to {$newStatus}",
            'severity' => 'info',
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
        ]);

        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $ticket->user_id,
        ]);

        SendRealtimeNotificationJob::dispatch($notification->id, $ticket->user_id);
    }

    private function broadcastStatusChange(SupportTicket $ticket, string $oldStatus, string $newStatus): void
    {
        try {
            $pusherService = app(PusherService::class);

            // Use the dedicated method for broadcasting status changes
            $pusherService->broadcastTicketStatusChange(
                $ticket->id,
                $ticket->ticket_number,
                $oldStatus,
                $newStatus,
                $ticket->user_id
            );

            Log::info('Broadcast ticket status change', [
                'ticket_id' => $ticket->id,
                'new_status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast ticket status change', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
