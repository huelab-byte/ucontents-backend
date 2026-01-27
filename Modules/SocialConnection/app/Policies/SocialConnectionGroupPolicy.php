<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Policies;

use Modules\SocialConnection\Models\SocialConnectionGroup;
use Modules\UserManagement\Models\User;

class SocialConnectionGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SocialConnectionGroup $group): bool
    {
        return $group->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SocialConnectionGroup $group): bool
    {
        return $group->user_id === $user->id;
    }

    public function delete(User $user, SocialConnectionGroup $group): bool
    {
        return $group->user_id === $user->id;
    }
}
