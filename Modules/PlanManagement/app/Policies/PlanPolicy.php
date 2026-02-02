<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Policies;

use Modules\UserManagement\Models\User;
use Modules\PlanManagement\Models\Plan;

class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_plans') || $user->hasPermission('manage_plans');
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->hasPermission('view_plans') || $user->hasPermission('manage_plans');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_plans');
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->hasPermission('manage_plans');
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->hasPermission('manage_plans');
    }

    public function restore(User $user, Plan $plan): bool
    {
        return $user->hasPermission('manage_plans');
    }

    public function forceDelete(User $user, Plan $plan): bool
    {
        return $user->hasPermission('manage_plans');
    }
}
