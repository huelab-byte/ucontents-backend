<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialConnectionChannel;

class DeleteChannelAction
{
    public function execute(SocialConnectionChannel $channel): void
    {
        $channel->delete();
    }
}

