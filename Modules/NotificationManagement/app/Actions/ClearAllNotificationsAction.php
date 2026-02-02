<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Actions;

use Modules\NotificationManagement\Models\NotificationRecipient;

class ClearAllNotificationsAction
{
    /**
     * Clear all notifications for a user by deleting recipient records.
     * This keeps the notification content for other recipients.
     *
     * @param int $userId
     * @return int Number of notifications deleted
     */
    public function execute(int $userId): int
    {
        return NotificationRecipient::query()
            ->where('user_id', $userId)
            ->delete();
    }
}
