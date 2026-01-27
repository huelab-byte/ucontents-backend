<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Policies;

use Modules\UserManagement\Models\User;
use Modules\AiIntegration\Models\AiUsageLog;

/**
 * Policy for AI Usage Log authorization
 */
class AiUsageLogPolicy
{
    /**
     * Determine if the user can view any usage logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_ai_usage');
    }

    /**
     * Determine if the user can view the usage log.
     */
    public function view(User $user, AiUsageLog $log): bool
    {
        return $user->can('view_ai_usage');
    }
}
