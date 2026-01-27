<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialConnectionChannel;

class DisconnectChannelAction
{
    public function execute(SocialConnectionChannel $channel): SocialConnectionChannel
    {
        $channel->update([
            'is_active' => false,
        ]);

        return $channel->fresh();
    }
}

