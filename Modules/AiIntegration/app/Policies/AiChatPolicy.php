<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\UserManagement\Models\User;

class AiChatPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can use AI chat.
     */
    public function use_chat(User $user): bool
    {
        return $user->hasPermission('use_ai_chat');
    }
}
