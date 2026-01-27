<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Email Facade
 * 
 * Provides easy access to email service throughout the application
 * 
 * @method static \Modules\EmailManagement\Models\EmailLog send(\Modules\EmailManagement\DTOs\SendEmailDTO $dto)
 * @method static \Modules\EmailManagement\Models\EmailLog sendWithTemplate(string $to, string $templateSlug, array $variables = [], ?int $smtpConfigurationId = null, bool $useQueue = true)
 * @method static \Modules\EmailManagement\Models\EmailLog sendNotification(string $to, string $subject, string $body, ?int $smtpConfigurationId = null, bool $useQueue = true)
 */
class EmailFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'email.service';
    }
}
