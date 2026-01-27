<?php

declare(strict_types=1);

namespace Modules\Support\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\Jobs\SendRealtimeNotificationJob;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\NotificationManagement\Services\PusherService;
use Modules\StorageManagement\Models\StorageFile;
use Modules\Support\DTOs\ReplySupportTicketDTO;
use Modules\Support\Http\Resources\SupportTicketReplyResource;
use Modules\Support\Models\SupportTicket;
use Modules\Support\Models\SupportTicketReply;
use Modules\Support\Models\SupportTicketAttachment;
use Modules\UserManagement\Models\User;

class ReplySupportTicketAction
{
    public function execute(SupportTicket $ticket, ReplySupportTicketDTO $dto, int $userId, bool $isAdmin = false): SupportTicketReply
    {
        // Prevent customers from replying to resolved/closed tickets
        if (!$isAdmin && in_array($ticket->status, ['resolved', 'closed'])) {
            throw new \Exception('Cannot reply to a ' . $ticket->status . ' ticket. Please create a new ticket if you need further assistance.');
        }

        return DB::transaction(function () use ($ticket, $dto, $userId, $isAdmin) {
            // Create reply
            $reply = SupportTicketReply::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $userId,
                'message' => $dto->message ?? '',
                'is_internal' => $dto->isInternal && $isAdmin,
            ]);

            // Attach files if provided
            if (!empty($dto->attachmentIds)) {
                $this->attachFiles($reply, $dto->attachmentIds, $userId);
            }

            // Update ticket
            $ticket->update([
                'last_replied_at' => now(),
                'last_replied_by_user_id' => $userId,
            ]);

            // Send notifications
            if ($isAdmin && !$dto->isInternal) {
                // Admin replied (not internal) - notify ticket creator
                $this->notifyUserTicketReplied($ticket, $reply);
            } elseif (!$isAdmin) {
                // User replied - notify assigned admin or all admins
                $this->notifyAdminTicketReplied($ticket, $reply);
            }

            // Broadcast real-time update for ticket reply
            $this->broadcastTicketReply($ticket, $reply, $isAdmin);

            Log::info('Support ticket reply created', [
                'ticket_id' => $ticket->id,
                'reply_id' => $reply->id,
                'user_id' => $userId,
                'is_admin' => $isAdmin,
                'is_internal' => $dto->isInternal,
            ]);

            return $reply;
        });
    }

    private function attachFiles(SupportTicketReply $reply, array $storageFileIds, int $userId): void
    {
        foreach ($storageFileIds as $storageFileId) {
            $storageFile = StorageFile::findOrFail($storageFileId);

            // Verify ownership (for security)
            if ($storageFile->user_id !== $userId) {
                throw new \Exception('Unauthorized file access');
            }

            SupportTicketAttachment::create([
                'support_ticket_id' => null,
                'support_ticket_reply_id' => $reply->id,
                'storage_file_id' => $storageFileId,
            ]);
        }
    }

    private function notifyUserTicketReplied(SupportTicket $ticket, SupportTicketReply $reply): void
    {
        $admin = $reply->user;
        $messagePreview = mb_substr($reply->message, 0, 100) . (mb_strlen($reply->message) > 100 ? '...' : '');

        $notification = Notification::create([
            'type' => 'support_ticket_replied',
            'title' => "New Reply on Ticket: {$ticket->ticket_number}",
            'body' => "{$admin->name} replied: {$messagePreview}",
            'severity' => 'info',
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'reply_id' => $reply->id,
                'is_admin_reply' => true,
            ],
        ]);

        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $ticket->user_id,
        ]);

        SendRealtimeNotificationJob::dispatch($notification->id, $ticket->user_id);
    }

    private function notifyAdminTicketReplied(SupportTicket $ticket, SupportTicketReply $reply): void
    {
        $user = $reply->user;
        $messagePreview = mb_substr($reply->message, 0, 100) . (mb_strlen($reply->message) > 100 ? '...' : '');

        $notification = Notification::create([
            'type' => 'support_ticket_replied',
            'title' => "New Reply on Ticket: {$ticket->ticket_number}",
            'body' => "{$user->name} replied: {$messagePreview}",
            'severity' => 'info',
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'reply_id' => $reply->id,
                'is_admin_reply' => false,
            ],
        ]);

        // Notify assigned admin, or all admins if not assigned
        if ($ticket->assigned_to_user_id) {
            $adminIds = [$ticket->assigned_to_user_id];
        } else {
            $adminIds = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super_admin', 'admin']))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        foreach ($adminIds as $adminId) {
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $adminId,
            ]);

            SendRealtimeNotificationJob::dispatch($notification->id, $adminId);
        }
    }

    private function broadcastTicketReply(SupportTicket $ticket, SupportTicketReply $reply, bool $isAdmin): void
    {
        try {
            $pusherService = app(PusherService::class);

            // Load reply with relationships for the broadcast
            $reply->load(['user', 'attachments.storageFile']);

            // Create reply resource for broadcast
            $replyData = (new SupportTicketReplyResource($reply))->resolve();

            // Broadcast via PusherService
            $pusherService->broadcastTicketReply(
                $ticket->id,
                $ticket->ticket_number,
                $replyData,
                $ticket->user_id,
                $reply->is_internal
            );
        } catch (\Exception $e) {
            // Log but don't fail the reply creation if Pusher fails
            Log::warning('Failed to broadcast ticket reply via Pusher', [
                'ticket_id' => $ticket->id,
                'reply_id' => $reply->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
