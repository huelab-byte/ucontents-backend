<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\SocialConnection\Models\SocialConnectionGroup;
use Modules\UserManagement\Models\User;

class BulkAssignGroupAction
{
    public function execute(User $user, array $channelIds, ?int $groupId): int
    {
        // Ensure all channels belong to the user
        $channels = SocialConnectionChannel::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $channelIds)
            ->get();

        if ($channels->count() !== count($channelIds)) {
            throw new \RuntimeException('Some channels do not belong to the user');
        }

        // If group_id is provided, verify it belongs to the user
        if ($groupId !== null) {
            $group = SocialConnectionGroup::query()
                ->where('id', $groupId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        }

        // Update all channels
        $updated = SocialConnectionChannel::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $channelIds)
            ->update(['group_id' => $groupId]);

        return $updated;
    }
}
