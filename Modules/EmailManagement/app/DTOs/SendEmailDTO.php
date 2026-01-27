<?php

declare(strict_types=1);

namespace Modules\EmailManagement\DTOs;

/**
 * Data Transfer Object for sending emails
 */
readonly class SendEmailDTO
{
    public function __construct(
        public string $to,
        public ?string $cc = null,
        public ?string $bcc = null,
        public string $subject,
        public string $body,
        public ?int $templateId = null,
        public ?array $templateVariables = null,
        public ?int $smtpConfigurationId = null,
        public bool $useQueue = true,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            to: $data['to'],
            cc: $data['cc'] ?? null,
            bcc: $data['bcc'] ?? null,
            subject: $data['subject'],
            body: $data['body'],
            templateId: $data['template_id'] ?? null,
            templateVariables: $data['template_variables'] ?? null,
            smtpConfigurationId: $data['smtp_configuration_id'] ?? null,
            useQueue: $data['use_queue'] ?? true,
            metadata: $data['metadata'] ?? null,
        );
    }
}
