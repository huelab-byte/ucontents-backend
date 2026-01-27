<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialConnectionChannel;

class UpdateChannelStatusAction
{
    public function execute(SocialConnectionChannel $channel, bool $isActive): SocialConnectionChannel
    {
        $channel->update([
            'is_active' => $isActive,
        ]);

        return $channel->fresh();
    }
}
