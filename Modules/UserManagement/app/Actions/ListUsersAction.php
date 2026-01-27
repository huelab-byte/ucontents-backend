<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\UserManagement\DTOs\ListUsersDTO;
use Modules\UserManagement\Models\User;

/**
 * Action to list users with filters
 */
class ListUsersAction
{
    public function execute(ListUsersDTO $dto): LengthAwarePaginator
    {
        $query = User::with(['roles.permissions']);

        // Filter by role
        if ($dto->role) {
            $query->whereHas('roles', function ($q) use ($dto) {
                $q->where('slug', $dto->role);
            });
        }

        // Search by name or email
        if ($dto->search) {
            $search = $dto->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($dto->perPage);
    }
}
