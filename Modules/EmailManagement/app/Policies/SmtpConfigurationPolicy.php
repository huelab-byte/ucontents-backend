<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Policies;

use Modules\UserManagement\Models\User;
use Modules\EmailManagement\Models\SmtpConfiguration;

/**
 * Policy for SMTP Configuration authorization
 */
class SmtpConfigurationPolicy
{
    /**
     * Determine if the user can view any configurations.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_email_config') || $user->can('manage_email_config');
    }

    /**
     * Determine if the user can view the configuration.
     */
    public function view(User $user, SmtpConfiguration $configuration): bool
    {
        return $user->can('view_email_config') || $user->can('manage_email_config');
    }

    /**
     * Determine if the user can create configurations.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_email_config');
    }

    /**
     * Determine if the user can update the configuration.
     */
    public function update(User $user, SmtpConfiguration $configuration): bool
    {
        return $user->can('manage_email_config');
    }

    /**
     * Determine if the user can delete the configuration.
     */
    public function delete(User $user, SmtpConfiguration $configuration): bool
    {
        return $user->can('manage_email_config');
    }

    /**
     * Determine if the user can set the configuration as default.
     */
    public function setDefault(User $user, SmtpConfiguration $configuration): bool
    {
        return $user->can('manage_email_config');
    }

    /**
     * Determine if the user can test the configuration.
     */
    public function test(User $user, SmtpConfiguration $configuration): bool
    {
        return $user->can('manage_email_config');
    }
}
