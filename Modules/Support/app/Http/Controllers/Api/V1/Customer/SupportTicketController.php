<?php

declare(strict_types=1);

namespace Modules\Support\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\Support\Actions\CreateSupportTicketAction;
use Modules\Support\Actions\ReplySupportTicketAction;
use Modules\Support\DTOs\CreateSupportTicketDTO;
use Modules\Support\DTOs\ReplySupportTicketDTO;
use Modules\Support\Http\Requests\ListSupportTicketsRequest;
use Modules\Support\Http\Requests\ReplySupportTicketRequest;
use Modules\Support\Http\Requests\StoreSupportTicketRequest;
use Modules\Support\Http\Resources\SupportTicketResource;
use Modules\Support\Models\SupportTicket;
use Modules\Support\Services\SupportTicketQueryService;

class SupportTicketController extends BaseApiController
{
    public function __construct(
        private CreateSupportTicketAction $createTicketAction,
        private ReplySupportTicketAction $replyAction,
        private SupportTicketQueryService $queryService
    ) {
    }

    /**
     * List user's own tickets
     */
    public function index(ListSupportTicketsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $validated = $request->validated();
        $filters = array_filter([
            'status' => $validated['status'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'search' => $validated['search'] ?? null,
        ], fn($v) => $v !== null);

        $tickets = $this->queryService->listForUserWithFilters(auth()->id(), $filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($tickets, SupportTicketResource::class, 'Tickets retrieved successfully');
    }

    /**
     * Show a specific ticket
     */
    public function show(SupportTicket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'user',
            'assignedTo',
            'lastRepliedBy',
            'publicReplies.user',
            'publicReplies.attachments.storageFile',
            'attachments.storageFile',
        ]);

        return $this->success(new SupportTicketResource($ticket), 'Ticket retrieved successfully');
    }

    /**
     * Create a new support ticket
     */
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $this->authorize('create', SupportTicket::class);

        $dto = CreateSupportTicketDTO::fromArray($request->validated());
        $ticket = $this->createTicketAction->execute($dto, auth()->id());

        $ticket->load(['user', 'attachments.storageFile']);

        return $this->created(new SupportTicketResource($ticket), 'Support ticket created successfully');
    }

    /**
     * Reply to a ticket
     */
    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('reply', $ticket);

        $dto = ReplySupportTicketDTO::fromArray($request->validated());
        $reply = $this->replyAction->execute($ticket, $dto, auth()->id(), false);

        $ticket->load([
            'user',
            'assignedTo',
            'lastRepliedBy',
            'publicReplies.user',
            'publicReplies.attachments.storageFile',
            'attachments.storageFile',
        ]);

        return $this->success(new SupportTicketResource($ticket), 'Reply added successfully');
    }
}
