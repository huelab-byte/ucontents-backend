<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\DTOs;

class CreateAnnouncementDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly bool $sendInApp,
        public readonly bool $sendEmail,
        public readonly string $audience, // all_admins|specific_users
        /** @var array<int> */
        public readonly array $userIds = [],
        public readonly ?string $severity = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $data = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) $data['title'],
            body: (string) $data['body'],
            sendInApp: (bool) ($data['send_in_app'] ?? true),
            sendEmail: (bool) ($data['send_email'] ?? false),
            audience: (string) ($data['audience'] ?? 'all_admins'),
            userIds: array_map('intval', $data['user_ids'] ?? []),
            severity: $data['severity'] ?? null,
            data: $data['data'] ?? null,
        );
    }
}

