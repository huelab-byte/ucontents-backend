<?php

declare(strict_types=1);

namespace Modules\Support\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\Support\Actions\AssignTicketAction;
use Modules\Support\Actions\ListSupportTicketsAction;
use Modules\Support\Actions\ReplySupportTicketAction;
use Modules\Support\Actions\UpdateTicketPriorityAction;
use Modules\Support\Actions\UpdateTicketStatusAction;
use Modules\Support\DTOs\AssignTicketDTO;
use Modules\Support\DTOs\ListSupportTicketsDTO;
use Modules\Support\DTOs\ReplySupportTicketDTO;
use Modules\Support\DTOs\UpdateTicketPriorityDTO;
use Modules\Support\DTOs\UpdateTicketStatusDTO;
use Modules\Support\Http\Requests\AssignTicketRequest;
use Modules\Support\Http\Requests\ListSupportTicketsRequest;
use Modules\Support\Http\Requests\ReplySupportTicketRequest;
use Modules\Support\Http\Requests\UpdateTicketPriorityRequest;
use Modules\Support\Http\Requests\UpdateTicketStatusRequest;
use Modules\Support\Http\Resources\SupportTicketResource;
use Modules\Support\Models\SupportTicket;

class SupportTicketController extends BaseApiController
{
    public function __construct(
        private ReplySupportTicketAction $replyAction,
        private UpdateTicketStatusAction $updateStatusAction,
        private AssignTicketAction $assignAction,
        private UpdateTicketPriorityAction $updatePriorityAction,
        private ListSupportTicketsAction $listAction
    ) {
    }

    /**
     * List all tickets
     */
    public function index(ListSupportTicketsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $dto = ListSupportTicketsDTO::fromArray($request->validated());
        $tickets = $this->listAction->execute($dto);

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
            'replies.user', // Include internal replies for admins
            'replies.attachments.storageFile',
            'attachments.storageFile',
        ]);

        return $this->success(new SupportTicketResource($ticket), 'Ticket retrieved successfully');
    }

    /**
     * Reply to a ticket
     */
    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('reply', $ticket);

        $dto = ReplySupportTicketDTO::fromArray($request->validated());
        $reply = $this->replyAction->execute($ticket, $dto, auth()->id(), true);

        $ticket->load([
            'user',
            'assignedTo',
            'lastRepliedBy',
            'replies.user',
            'replies.attachments.storageFile',
            'attachments.storageFile',
        ]);

        return $this->success(new SupportTicketResource($ticket), 'Reply added successfully');
    }

    /**
     * Update ticket status
     */
    public function updateStatus(UpdateTicketStatusRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $dto = UpdateTicketStatusDTO::fromArray($request->validated());
        $ticket = $this->updateStatusAction->execute($ticket, $dto);

        $ticket->load([
            'user',
            'assignedTo',
            'lastRepliedBy',
            'replies.user',
            'replies.attachments.storageFile',
            'attachments.storageFile',
        ]);

        return $this->success(new SupportTicketResource($ticket), 'Ticket status updated successfully');
    }

    /**
     * Assign ticket
     */
    public function assign(AssignTicketRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $dto = AssignTicketDTO::fromArray($request->validated());
        $ticket = $this->assignAction->execute($ticket, $dto, auth()->id());

        $ticket->load([
            'user',
            'assignedTo',
            'lastRepliedBy',
            'replies.user',
            'replies.attachments.storageFile',
            'attachments.storageFile',
        ]);

        return $this->success(new SupportTicketResource($ticket), 'Ticket assigned successfully');
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(UpdateTicketPriorityRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $dto = UpdateTicketPriorityDTO::fromArray($request->validated());
        $ticket = $this->updatePriorityAction->execute($ticket, $dto);

        $ticket->load([
            'user',
            'assignedTo',
            'lastRepliedBy',
            'replies.user',
            'replies.attachments.storageFile',
            'attachments.storageFile',
        ]);

        return $this->success(new SupportTicketResource($ticket), 'Ticket priority updated successfully');
    }
}
