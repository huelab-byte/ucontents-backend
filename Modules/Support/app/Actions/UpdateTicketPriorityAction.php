<?php

declare(strict_types=1);

namespace Modules\Support\Actions;

use Illuminate\Support\Facades\Log;
use Modules\Support\DTOs\UpdateTicketPriorityDTO;
use Modules\Support\Models\SupportTicket;

class UpdateTicketPriorityAction
{
    public function execute(SupportTicket $ticket, UpdateTicketPriorityDTO $dto): SupportTicket
    {
        $ticket->update([
            'priority' => $dto->priority,
        ]);

        Log::info('Support ticket priority updated', [
            'ticket_id' => $ticket->id,
            'priority' => $dto->priority,
        ]);

        return $ticket->fresh();
    }
}
