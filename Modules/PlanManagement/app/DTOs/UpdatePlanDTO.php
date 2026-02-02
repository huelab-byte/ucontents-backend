<?php

declare(strict_types=1);

namespace Modules\PlanManagement\DTOs;

readonly class UpdatePlanDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?int $aiUsageLimit = null,
        public ?int $maxFileUpload = null,
        public ?int $totalStorageBytes = null,
        public ?array $features = null,
        public ?int $maxConnections = null,
        public ?int $monthlyPostLimit = null,
        public ?string $subscriptionType = null,
        public ?float $price = null,
        public ?string $currency = null,
        public ?bool $isActive = null,
        public ?int $sortOrder = null,
        public ?bool $featured = null,
        public ?bool $isFreePlan = null,
        public ?int $trialDays = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            aiUsageLimit: array_key_exists('ai_usage_limit', $data) ? (int) $data['ai_usage_limit'] : null,
            maxFileUpload: array_key_exists('max_file_upload', $data) ? (int) $data['max_file_upload'] : null,
            totalStorageBytes: array_key_exists('total_storage_bytes', $data) ? (int) $data['total_storage_bytes'] : null,
            features: array_key_exists('features', $data) ? $data['features'] : null,
            maxConnections: array_key_exists('max_connections', $data) ? (int) $data['max_connections'] : null,
            monthlyPostLimit: array_key_exists('monthly_post_limit', $data) ? (int) $data['monthly_post_limit'] : null,
            subscriptionType: $data['subscription_type'] ?? null,
            price: array_key_exists('price', $data) ? (float) $data['price'] : null,
            currency: $data['currency'] ?? null,
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            sortOrder: array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : null,
            featured: array_key_exists('featured', $data) ? (bool) $data['featured'] : null,
            isFreePlan: array_key_exists('is_free_plan', $data) ? (bool) $data['is_free_plan'] : null,
            trialDays: array_key_exists('trial_days', $data) ? (int) $data['trial_days'] : null,
        );
    }
}
