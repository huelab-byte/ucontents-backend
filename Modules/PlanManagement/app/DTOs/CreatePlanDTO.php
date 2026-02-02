<?php

declare(strict_types=1);

namespace Modules\PlanManagement\DTOs;

readonly class CreatePlanDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public ?int $aiUsageLimit = null,
        public int $maxFileUpload = 0,
        public int $totalStorageBytes = 0,
        public ?array $features = null,
        public int $maxConnections = 0,
        public ?int $monthlyPostLimit = null,
        public string $subscriptionType = 'monthly',
        public float $price = 0,
        public string $currency = 'USD',
        public bool $isActive = true,
        public int $sortOrder = 0,
        public bool $featured = false,
        public bool $isFreePlan = false,
        public ?int $trialDays = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            aiUsageLimit: isset($data['ai_usage_limit']) ? (int) $data['ai_usage_limit'] : null,
            maxFileUpload: (int) ($data['max_file_upload'] ?? 0),
            totalStorageBytes: (int) ($data['total_storage_bytes'] ?? 0),
            features: $data['features'] ?? null,
            maxConnections: (int) ($data['max_connections'] ?? 0),
            monthlyPostLimit: isset($data['monthly_post_limit']) ? (int) $data['monthly_post_limit'] : null,
            subscriptionType: $data['subscription_type'] ?? 'monthly',
            price: (float) ($data['price'] ?? 0),
            currency: $data['currency'] ?? 'USD',
            isActive: (bool) ($data['is_active'] ?? true),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            featured: (bool) ($data['featured'] ?? false),
            isFreePlan: (bool) ($data['is_free_plan'] ?? false),
            trialDays: isset($data['trial_days']) ? (int) $data['trial_days'] : null,
        );
    }
}
