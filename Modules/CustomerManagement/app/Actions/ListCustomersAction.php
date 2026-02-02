<?php

declare(strict_types=1);

namespace Modules\CustomerManagement\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\CustomerManagement\DTOs\ListCustomersDTO;
use Modules\UserManagement\Models\User;

class ListCustomersAction
{
    public function execute(ListCustomersDTO $dto): LengthAwarePaginator
    {
        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'customer'))
            ->with(['roles']);

        if ($dto->search !== null && $dto->search !== '') {
            $search = $dto->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($dto->status !== null && $dto->status !== '') {
            $query->where('status', $dto->status);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
