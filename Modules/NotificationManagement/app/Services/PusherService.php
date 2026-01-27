<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Services;

use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\Http\Resources\NotificationResource;
use Modules\NotificationManagement\Http\Resources\NotificationRecipientResource;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;

class PusherService
{
    /**
     * Create and return a configured Pusher client.
     */
    private function client()
    {
        if (!class_exists(\Pusher\Pusher::class)) {
            throw new \RuntimeException('Pusher PHP SDK not installed. Require pusher/pusher-php-server.');
        }

        // Try database settings first, then config/env
        $settingsService = app(\Modules\GeneralSettings\Services\GeneralSettingsService::class);
        
        $key = (string) ($settingsService->get('notification.pusher.key') 
            ?: config('notificationmanagement.pusher.key', env('PUSHER_APP_KEY')));
        $secret = (string) ($settingsService->get('notification.pusher.secret') 
            ?: config('notificationmanagement.pusher.secret', env('PUSHER_APP_SECRET')));
        $appId = (string) ($settingsService->get('notification.pusher.app_id') 
            ?: config('notificationmanagement.pusher.app_id', env('PUSHER_APP_ID')));
        $cluster = (string) ($settingsService->get('notification.pusher.cluster') 
            ?: config('notificationmanagement.pusher.cluster', env('PUSHER_APP_CLUSTER', 'mt1')));

        if ($key === '' || $secret === '' || $appId === '') {
            throw new \RuntimeException('Pusher credentials are not configured.');
        }

        return new \Pusher\Pusher(
            $key,
            $secret,
            $appId,
            [
                'cluster' => $cluster,
                'useTLS' => true,
            ]
        );
    }

    public function authorizePrivateChannel(string $socketId, string $channelName, ?string $userData = null): array
    {
        $pusher = $this->client();

        // For private channels, $userData should be null
        // For presence channels, $userData should be a JSON string
        // pusher-php-server v7.x expects string|null for the third parameter
        $raw = $pusher->authorizeChannel($channelName, $socketId, $userData);
        return is_string($raw) ? json_decode($raw, true) : (array) $raw;
    }

    public function sendNotificationCreated(NotificationRecipient $recipient): void
    {
        $pusher = $this->client();

        $channel = 'private-user.' . $recipient->user_id;
        $event = 'notification.created';

        $payload = (new NotificationRecipientResource($recipient->loadMissing('notification')))->resolve();

        Log::debug('Pusher notification.created', [
            'channel' => $channel,
            'event' => $event,
            'recipient_id' => $recipient->id,
        ]);

        $pusher->trigger($channel, $event, $payload);
    }

    public function sendAdminsNotificationCreated(NotificationRecipient $recipient): void
    {
        $pusher = $this->client();

        $channel = 'private-admins';
        $event = 'notification.created';

        $payload = (new NotificationRecipientResource($recipient->loadMissing('notification')))->resolve();

        $pusher->trigger($channel, $event, $payload);
    }

    public function sendAdminsAnnouncementCreated(Notification $notification): void
    {
        $pusher = $this->client();

        $channel = 'private-admins';
        $event = 'announcement.created';

        $payload = (new NotificationResource($notification))->resolve();

        $pusher->trigger($channel, $event, $payload);
    }

    /**
     * Broadcast a support ticket status change
     */
    public function broadcastTicketStatusChange(int $ticketId, string $ticketNumber, string $oldStatus, string $newStatus, int $ticketOwnerId): void
    {
        try {
            $pusher = $this->client();

            $payload = [
                'ticket_id' => $ticketId,
                'ticket_number' => $ticketNumber,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ];

            // Broadcast to ticket owner (customer)
            $customerChannel = 'private-user.' . $ticketOwnerId;
            Log::info('Broadcasting ticket status change to customer', [
                'channel' => $customerChannel,
                'event' => 'ticket-status-changed',
                'ticket_id' => $ticketId,
                'new_status' => $newStatus,
            ]);
            $pusher->trigger($customerChannel, 'ticket-status-changed', $payload);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast ticket status change via Pusher', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast a support ticket reply update
     */
    public function broadcastTicketReply(int $ticketId, string $ticketNumber, array $replyData, int $ticketOwnerId, bool $isInternal): void
    {
        try {
            $pusher = $this->client();

            $payload = [
                'ticket_id' => $ticketId,
                'ticket_number' => $ticketNumber,
                'reply' => $replyData,
            ];

            // Broadcast to ticket owner (customer) if not internal
            if (!$isInternal) {
                $customerChannel = 'private-user.' . $ticketOwnerId;
                Log::info('Broadcasting ticket reply to customer', [
                    'channel' => $customerChannel,
                    'event' => 'support.ticket.reply',
                    'ticket_id' => $ticketId,
                    'ticket_owner_id' => $ticketOwnerId,
                ]);
                $result = $pusher->trigger($customerChannel, 'support.ticket.reply', $payload);
                Log::info('Pusher trigger result for customer', ['result' => $result]);
            }

            // Broadcast to admins
            $adminChannel = 'private-admins';
            Log::info('Broadcasting ticket reply to admins', [
                'channel' => $adminChannel,
                'event' => 'support.ticket.reply',
                'ticket_id' => $ticketId,
            ]);
            $result = $pusher->trigger($adminChannel, 'support.ticket.reply', $payload);
            Log::info('Pusher trigger result for admins', ['result' => $result]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast ticket reply via Pusher', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

