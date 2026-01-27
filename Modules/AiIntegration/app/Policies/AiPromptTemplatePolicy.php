<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Policies;

use Modules\UserManagement\Models\User;
use Modules\AiIntegration\Models\AiPromptTemplate;

/**
 * Policy for AI Prompt Template authorization
 */
class AiPromptTemplatePolicy
{
    /**
     * Determine if the user can view any templates.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view templates
    }

    /**
     * Determine if the user can view the template.
     */
    public function view(User $user, AiPromptTemplate $template): bool
    {
        return $template->is_active || $user->can('manage_prompt_templates');
    }

    /**
     * Determine if the user can create templates.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_prompt_templates');
    }

    /**
     * Determine if the user can update the template.
     */
    public function update(User $user, AiPromptTemplate $template): bool
    {
        return $user->can('manage_prompt_templates');
    }

    /**
     * Determine if the user can delete the template.
     */
    public function delete(User $user, AiPromptTemplate $template): bool
    {
        if ($template->is_system) {
            return false;
        }

        return $user->can('manage_prompt_templates');
    }
}
