<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Policies;

use Modules\UserManagement\Models\User;
use Modules\EmailManagement\Models\EmailTemplate;

/**
 * Policy for Email Template authorization
 */
class EmailTemplatePolicy
{
    /**
     * Determine if the user can view any templates.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_email_templates') || $user->can('manage_email_templates');
    }

    /**
     * Determine if the user can view the template.
     */
    public function view(User $user, EmailTemplate $template): bool
    {
        return $user->can('view_email_templates') || $user->can('manage_email_templates');
    }

    /**
     * Determine if the user can create templates.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_email_templates');
    }

    /**
     * Determine if the user can update the template.
     */
    public function update(User $user, EmailTemplate $template): bool
    {
        return $user->can('manage_email_templates');
    }

    /**
     * Determine if the user can delete the template.
     */
    public function delete(User $user, EmailTemplate $template): bool
    {
        return $user->can('manage_email_templates');
    }
}
