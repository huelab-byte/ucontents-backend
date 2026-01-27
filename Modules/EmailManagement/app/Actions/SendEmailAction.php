<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Actions;

use Modules\EmailManagement\DTOs\SendEmailDTO;
use Modules\EmailManagement\Models\EmailLog;
use Modules\EmailManagement\Services\EmailService;

/**
 * Action to send email
 */
class SendEmailAction
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    public function execute(SendEmailDTO $dto): EmailLog
    {
        return $this->emailService->send($dto);
    }
}
