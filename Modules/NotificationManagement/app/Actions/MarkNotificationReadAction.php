<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Actions;

use Modules\NotificationManagement\Models\NotificationRecipient;

class MarkNotificationReadAction
{
    public function execute(NotificationRecipient $recipient): NotificationRecipient
    {
        $recipient->markRead();
        return $recipient->fresh(['notification']) ?? $recipient;
    }
}

