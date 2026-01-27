<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Policies;

use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\UserManagement\Models\User;

class NotificationRecipientPolicy
{
    /**
     * Determine if the user can update the notification recipient (mark as read).
     */
    public function update(User $user, NotificationRecipient $recipient): bool
    {
        return $recipient->user_id === $user->id;
    }
}
