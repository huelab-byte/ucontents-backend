<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\DTOs\UpdateGroupDTO;
use Modules\SocialConnection\Models\SocialConnectionGroup;

class UpdateGroupAction
{
    /**
     * Execute the action to update a group
     *
     * @param SocialConnectionGroup $group
     * @param UpdateGroupDTO|string $data DTO or legacy string name
     * @return SocialConnectionGroup
     */
    public function execute(SocialConnectionGroup $group, UpdateGroupDTO|string $data): SocialConnectionGroup
    {
        $name = $data instanceof UpdateGroupDTO ? $data->name : $data;

        $group->update([
            'name' => $name,
        ]);

        return $group->fresh();
    }
}
