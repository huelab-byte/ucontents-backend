<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\DTOs\CreateAnnouncementDTO;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\UserManagement\Models\User;

class CreateAnnouncementAction
{
    /**
     * @return array{notification: Notification, recipients_created: int, recipient_user_ids: array<int>}
     */
    public function execute(CreateAnnouncementDTO $dto, ?int $createdByUserId = null): array
    {
        return DB::transaction(function () use ($dto, $createdByUserId) {
            $notification = Notification::create([
                'type' => 'announcement',
                'title' => $dto->title,
                'body' => $dto->body,
                'data' => $dto->data,
                'severity' => $dto->severity,
                'created_by_user_id' => $createdByUserId,
            ]);

            $userIds = $this->resolveRecipientUserIds($dto);

            $rows = array_map(
                fn (int $userId) => [
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $userIds
            );

            $created = 0;
            foreach (array_chunk($rows, 1000) as $chunk) {
                // Insert ignores duplicates via unique constraint if any concurrency occurs
                $created += NotificationRecipient::query()->insertOrIgnore($chunk);
            }

            Log::info('Announcement created', [
                'notification_id' => $notification->id,
                'audience' => $dto->audience,
                'recipients_created' => $created,
                'send_in_app' => $dto->sendInApp,
                'send_email' => $dto->sendEmail,
            ]);

            return [
                'notification' => $notification,
                'recipients_created' => $created,
                'recipient_user_ids' => $userIds,
            ];
        });
    }

    /**
     * @return array<int>
     */
    private function resolveRecipientUserIds(CreateAnnouncementDTO $dto): array
    {
        if ($dto->audience === 'specific_users') {
            return array_values(array_unique(array_map('intval', $dto->userIds)));
        }

        // all_admins
        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['super_admin', 'admin']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }
}

