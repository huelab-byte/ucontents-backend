<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialConnectionGroup;

class DeleteGroupAction
{
    public function execute(SocialConnectionGroup $group): void
    {
        // Set all channels in this group to null (cascade handled by DB foreign key)
        $group->channels()->update(['group_id' => null]);
        
        $group->delete();
    }
}
