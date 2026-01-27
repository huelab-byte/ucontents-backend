<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Policies;

use Modules\PaymentGateway\Models\Invoice;
use Modules\UserManagement\Models\User;

class InvoicePolicy
{
    /**
     * Determine if the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_all_invoices') || $user->hasPermission('view_own_invoices');
    }

    /**
     * Determine if the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Admins can view all invoices
        if ($user->hasPermission('view_all_invoices')) {
            return true;
        }

        // Users can view their own invoices
        return $user->hasPermission('view_own_invoices') && $invoice->user_id === $user->id;
    }

    /**
     * Determine if the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('view_all_invoices');
    }

    /**
     * Determine if the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('edit_invoices');
    }

    /**
     * Determine if the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('edit_invoices') && !$invoice->isPaid();
    }
}
