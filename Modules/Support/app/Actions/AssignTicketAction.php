<?php

declare(strict_types=1);

namespace Modules\Support\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\Jobs\SendRealtimeNotificationJob;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\Support\DTOs\AssignTicketDTO;
use Modules\Support\Models\SupportTicket;

class AssignTicketAction
{
    public function execute(SupportTicket $ticket, AssignTicketDTO $dto, int $assignedByUserId): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $dto, $assignedByUserId) {
            $ticket->update([
                'assigned_to_user_id' => $dto->assignedToUserId,
            ]);

            // Notify assigned admin if provided
            if ($dto->assignedToUserId) {
                $this->notifyAdminAssigned($ticket, $assignedByUserId);
            }

            Log::info('Support ticket assigned', [
                'ticket_id' => $ticket->id,
                'assigned_to_user_id' => $dto->assignedToUserId,
                'assigned_by_user_id' => $assignedByUserId,
            ]);

            return $ticket->fresh();
        });
    }

    private function notifyAdminAssigned(SupportTicket $ticket, int $assignedByUserId): void
    {
        $notification = Notification::create([
            'type' => 'support_ticket_assigned',
            'title' => "Ticket Assigned to You: {$ticket->ticket_number}",
            'body' => "You have been assigned ticket: {$ticket->subject}",
            'severity' => 'info',
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'assigned_by_user_id' => $assignedByUserId,
            ],
        ]);

        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $ticket->assigned_to_user_id,
        ]);

        SendRealtimeNotificationJob::dispatch($notification->id, $ticket->assigned_to_user_id);
    }
}
