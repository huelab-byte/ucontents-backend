<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Policies;

use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\UserManagement\Models\User;

class SocialProviderAppPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_social_connection_providers');
    }

    public function view(User $user, SocialProviderApp $app): bool
    {
        return $user->hasPermission('manage_social_connection_providers');
    }

    public function update(User $user, SocialProviderApp $app): bool
    {
        return $user->hasPermission('manage_social_connection_providers');
    }
}

