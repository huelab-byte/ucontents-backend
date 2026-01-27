<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Policies;

use Modules\UserManagement\Models\User;
use Modules\AiIntegration\Models\AiApiKey;

/**
 * Policy for AI API Key authorization
 */
class AiApiKeyPolicy
{
    /**
     * Check if user is super admin or has permission
     */
    private function canManage(User $user): bool
    {
        // Get fresh user with roles loaded
        $freshUser = User::with('roles')->find($user->id);
        
        if (!$freshUser) {
            return false;
        }
        
        // Super admin bypass
        if ($freshUser->roles()->where('slug', 'super_admin')->exists()) {
            return true;
        }
        
        // Check permission
        return $freshUser->hasPermission('manage_ai_api_keys');
    }

    /**
     * Determine if the user can view any API keys.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine if the user can view the API key.
     */
    public function view(User $user, AiApiKey $apiKey): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine if the user can create API keys.
     */
    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine if the user can update the API key.
     */
    public function update(User $user, AiApiKey $apiKey): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine if the user can delete the API key.
     */
    public function delete(User $user, AiApiKey $apiKey): bool
    {
        return $this->canManage($user);
    }
}
