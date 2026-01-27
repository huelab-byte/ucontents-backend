<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\DTOs\CreateGroupDTO;
use Modules\SocialConnection\Models\SocialConnectionGroup;
use Modules\UserManagement\Models\User;

class CreateGroupAction
{
    /**
     * Execute the action to create a group
     *
     * @param User $user
     * @param CreateGroupDTO|string $data DTO or legacy string name
     * @return SocialConnectionGroup
     */
    public function execute(User $user, CreateGroupDTO|string $data): SocialConnectionGroup
    {
        $name = $data instanceof CreateGroupDTO ? $data->name : $data;

        return SocialConnectionGroup::create([
            'user_id' => $user->id,
            'name' => $name,
        ]);
    }
}
