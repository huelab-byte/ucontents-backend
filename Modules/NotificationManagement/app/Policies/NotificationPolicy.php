<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Policies;

use Modules\NotificationManagement\Models\Notification;
use Modules\UserManagement\Models\User;

/**
 * Policy for Notification authorization
 */
class NotificationPolicy
{
    /**
     * Determine if the user can view any notifications.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_notifications') || $user->can('manage_notifications');
    }

    /**
     * Determine if the user can view the notification.
     */
    public function view(User $user, Notification $notification): bool
    {
        return $user->can('view_notifications') || $user->can('manage_notifications');
    }

    /**
     * Determine if the user can create notifications.
     */
    public function create(User $user): bool
    {
        return $user->can('create_notifications') || $user->can('manage_notifications');
    }

    /**
     * Determine if the user can update the notification.
     */
    public function update(User $user, Notification $notification): bool
    {
        return $user->can('manage_notifications');
    }

    /**
     * Determine if the user can delete the notification.
     */
    public function delete(User $user, Notification $notification): bool
    {
        return $user->can('manage_notifications');
    }
}
