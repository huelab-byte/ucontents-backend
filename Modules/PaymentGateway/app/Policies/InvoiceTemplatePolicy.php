<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Policies;

use App\Models\User;
use Modules\PaymentGateway\Models\InvoiceTemplate;

class InvoiceTemplatePolicy
{
    /**
     * Determine if the user can view any templates.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('edit_invoices');
    }

    /**
     * Determine if the user can view the template.
     */
    public function view(User $user, InvoiceTemplate $template): bool
    {
        return $user->can('edit_invoices');
    }

    /**
     * Determine if the user can create templates.
     */
    public function create(User $user): bool
    {
        return $user->can('edit_invoices');
    }

    /**
     * Determine if the user can update the template.
     */
    public function update(User $user, InvoiceTemplate $template): bool
    {
        return $user->can('edit_invoices');
    }

    /**
     * Determine if the user can delete the template.
     */
    public function delete(User $user, InvoiceTemplate $template): bool
    {
        return $user->can('edit_invoices');
    }
}
