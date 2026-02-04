<?php

declare(strict_types=1);

namespace Modules\AiIntegration\DTOs;

/**
 * DTO for AI usage filtering parameters
 */
class AiUsageFilterDTO
{
    public function __construct(
        public readonly ?string $providerSlug = null,
        public readonly ?int $userId = null,
        public readonly ?string $status = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly int $perPage = 15,
    ) {}

    public static function fromArray(array $data): self
    {
        $sanitized = [];
        foreach (['provider_slug', 'status', 'date_from', 'date_to'] as $field) {
            $val = $data[$field] ?? null;
            $sanitized[$field] = ($val === '' || $val === 'undefined') ? null : $val;
        }

        return new self(
            providerSlug: $sanitized['provider_slug'],
            userId: !empty($data['user_id']) ? (int) $data['user_id'] : null,
            status: $sanitized['status'],
            dateFrom: $sanitized['date_from'],
            dateTo: $sanitized['date_to'],
            perPage: (int) ($data['per_page'] ?? 15),
        );
    }
}
