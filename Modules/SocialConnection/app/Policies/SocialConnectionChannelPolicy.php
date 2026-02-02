<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Policies;

use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\UserManagement\Models\User;

class SocialConnectionChannelPolicy
{
    public function viewAny(User $user): bool
    {
        // Customer-owned: any authenticated user can view their channels.
        return true;
    }

    public function create(User $user): bool
    {
        // Any authenticated user can connect a new channel (OAuth redirect).
        return true;
    }

    public function view(User $user, SocialConnectionChannel $channel): bool
    {
        return $channel->user_id === $user->id;
    }

    public function update(User $user, SocialConnectionChannel $channel): bool
    {
        return $channel->user_id === $user->id;
    }

    public function delete(User $user, SocialConnectionChannel $channel): bool
    {
        return $channel->user_id === $user->id;
    }
}

