<?php

declare(strict_types=1);

namespace Modules\Support\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\Jobs\SendRealtimeNotificationJob;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\StorageManagement\Models\StorageFile;
use Modules\Support\DTOs\CreateSupportTicketDTO;
use Modules\Support\Models\SupportTicket;
use Modules\Support\Models\SupportTicketAttachment;
use Modules\UserManagement\Models\User;

class CreateSupportTicketAction
{
    public function execute(CreateSupportTicketDTO $dto, int $userId): SupportTicket
    {
        return DB::transaction(function () use ($dto, $userId) {
            // Generate ticket number
            $ticketNumber = $this->generateTicketNumber();

            // Create ticket
            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $userId,
                'subject' => $dto->subject,
                'description' => $dto->description,
                'status' => 'open',
                'priority' => $dto->priority,
                'category' => $dto->category,
            ]);

            // Attach files if provided
            if (!empty($dto->attachmentIds)) {
                $this->attachFiles($ticket, $dto->attachmentIds, $userId);
            }

            // Notify all admins
            $this->notifyAdminsTicketCreated($ticket);

            Log::info('Support ticket created', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => $userId,
            ]);

            return $ticket;
        });
    }

    private function generateTicketNumber(): string
    {
        $year = date('Y');
        $lastTicket = SupportTicket::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastTicket ? ((int) substr($lastTicket->ticket_number, -4)) + 1 : 1;

        return sprintf('TKT-%s-%04d', $year, $sequence);
    }

    private function attachFiles(SupportTicket $ticket, array $storageFileIds, int $userId): void
    {
        foreach ($storageFileIds as $storageFileId) {
            $storageFile = StorageFile::findOrFail($storageFileId);

            // Verify ownership (for security)
            if ($storageFile->user_id !== $userId) {
                throw new \Exception('Unauthorized file access');
            }

            SupportTicketAttachment::create([
                'support_ticket_id' => $ticket->id,
                'support_ticket_reply_id' => null,
                'storage_file_id' => $storageFileId,
            ]);
        }
    }

    private function notifyAdminsTicketCreated(SupportTicket $ticket): void
    {
        $user = $ticket->user;

        $notification = Notification::create([
            'type' => 'support_ticket_created',
            'title' => "New Support Ticket: {$ticket->ticket_number}",
            'body' => "{$user->name} created ticket: {$ticket->subject}",
            'severity' => 'info',
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => $ticket->user_id,
            ],
        ]);

        // Get admin user IDs
        $adminIds = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super_admin', 'admin']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        // Create recipients and send realtime
        foreach ($adminIds as $adminId) {
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $adminId,
            ]);

            SendRealtimeNotificationJob::dispatch($notification->id, $adminId);
        }
    }
}
