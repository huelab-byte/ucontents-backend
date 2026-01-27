<?php

declare(strict_types=1);

namespace Modules\Support\Policies;

use Modules\Support\Models\SupportTicket;
use Modules\UserManagement\Models\User;

class SupportTicketPolicy
{
    /**
     * Determine if the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all tickets
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Users can view their own tickets
        return $user->hasPermission('view_own_tickets');
    }

    /**
     * Determine if the user can view the ticket.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        // Admins can view all tickets
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Users can only view their own tickets
        return $ticket->user_id === $user->id && $user->hasPermission('view_own_tickets');
    }

    /**
     * Determine if the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_tickets');
    }

    /**
     * Determine if the user can update the ticket.
     */
    public function update(User $user, SupportTicket $ticket): bool
    {
        // Only admins can update tickets
        return $user->hasAnyRole(['super_admin', 'admin']) && $user->hasPermission('manage_tickets');
    }

    /**
     * Determine if the user can reply to the ticket.
     */
    public function reply(User $user, SupportTicket $ticket): bool
    {
        // Admins can always reply
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $user->hasPermission('manage_tickets');
        }

        // Users can reply to their own tickets
        return $ticket->user_id === $user->id && $user->hasPermission('reply_to_own_tickets');
    }

    /**
     * Determine if the user can delete the ticket.
     */
    public function delete(User $user, SupportTicket $ticket): bool
    {
        // Only admins can delete tickets
        return $user->hasAnyRole(['super_admin', 'admin']) && $user->hasPermission('manage_tickets');
    }
}
