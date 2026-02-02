<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Actions;

use Modules\NotificationManagement\Models\NotificationRecipient;

class MarkAllNotificationsReadAction
{
    /**
     * Mark all unread notifications as read for a user.
     *
     * @param int $userId
     * @return int Number of notifications marked as read
     */
    public function execute(int $userId): int
    {
        return NotificationRecipient::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
