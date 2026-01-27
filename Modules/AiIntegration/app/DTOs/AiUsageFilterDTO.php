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
        return new self(
            providerSlug: $data['provider_slug'] ?? null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            status: $data['status'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            perPage: (int) ($data['per_page'] ?? 15),
        );
    }
}
