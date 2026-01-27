<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\NotificationManagement\Models\NotificationRecipient;

class NotificationQueryService
{
    public function listForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return NotificationRecipient::query()
            ->with(['notification'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function unreadCountForUser(int $userId): int
    {
        return NotificationRecipient::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}

