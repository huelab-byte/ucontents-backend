<?php

declare(strict_types=1);

namespace Modules\Support\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Support\DTOs\ListSupportTicketsDTO;
use Modules\Support\Models\SupportTicket;

/**
 * Action to list support tickets with filters
 */
class ListSupportTicketsAction
{
    public function execute(ListSupportTicketsDTO $dto): LengthAwarePaginator
    {
        $query = SupportTicket::with(['user', 'assignedTo', 'lastRepliedBy']);

        // Filter by status
        if ($dto->status) {
            $query->where('status', $dto->status);
        }

        // Filter by priority
        if ($dto->priority) {
            $query->where('priority', $dto->priority);
        }

        // Filter by assigned admin
        if ($dto->assignedTo) {
            $query->where('assigned_to_user_id', $dto->assignedTo);
        }

        // Filter by ticket creator
        if ($dto->userId) {
            $query->where('user_id', $dto->userId);
        }

        // Search
        if ($dto->search) {
            $search = $dto->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($dto->perPage);
    }
}
