<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Policies;

use Modules\UserManagement\Models\User;
use Modules\AiIntegration\Models\AiProvider;

/**
 * Policy for AI Provider authorization
 */
class AiProviderPolicy
{
    /**
     * Determine if the user can view any providers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_ai_providers');
    }

    /**
     * Determine if the user can view the provider.
     */
    public function view(User $user, AiProvider $provider): bool
    {
        return $user->can('manage_ai_providers');
    }
}
