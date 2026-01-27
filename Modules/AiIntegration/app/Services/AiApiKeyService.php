<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Services;

use Modules\AiIntegration\DTOs\CreateApiKeyDTO;
use Modules\AiIntegration\DTOs\UpdateApiKeyDTO;
use Modules\AiIntegration\Models\AiApiKey;
use Modules\AiIntegration\Models\AiProvider;

/**
 * Service for managing AI API keys
 */
class AiApiKeyService
{
    /**
     * Create a new API key
     */
    public function createApiKey(CreateApiKeyDTO $dto): AiApiKey
    {
        return AiApiKey::create([
            'provider_id' => $dto->providerId,
            'name' => $dto->name,
            'api_key' => $dto->apiKey,
            'api_secret' => $dto->apiSecret,
            'endpoint_url' => $dto->endpointUrl,
            'organization_id' => $dto->organizationId,
            'project_id' => $dto->projectId,
            'is_active' => $dto->isActive,
            'priority' => $dto->priority,
            'rate_limit_per_minute' => $dto->rateLimitPerMinute,
            'rate_limit_per_day' => $dto->rateLimitPerDay,
            'metadata' => $dto->metadata,
        ]);
    }

    /**
     * Update an API key
     */
    public function updateApiKey(AiApiKey $apiKey, UpdateApiKeyDTO $dto): AiApiKey
    {
        $updateData = array_filter([
            'name' => $dto->name,
            'api_key' => $dto->apiKey,
            'api_secret' => $dto->apiSecret,
            'endpoint_url' => $dto->endpointUrl,
            'organization_id' => $dto->organizationId,
            'project_id' => $dto->projectId,
            'is_active' => $dto->isActive,
            'priority' => $dto->priority,
            'rate_limit_per_minute' => $dto->rateLimitPerMinute,
            'rate_limit_per_day' => $dto->rateLimitPerDay,
            'metadata' => $dto->metadata,
        ], fn($value) => $value !== null);

        $apiKey->update($updateData);

        return $apiKey->fresh();
    }

    /**
     * Get best available API key for a provider
     * 
     * @param string $providerSlug Provider slug
     * @param int|null $preferredKeyId Preferred API key ID (if specified)
     * @return AiApiKey|null
     */
    public function getBestApiKey(string $providerSlug, ?int $preferredKeyId = null): ?AiApiKey
    {
        $provider = AiProvider::where('slug', $providerSlug)->first();
        
        if (!$provider) {
            return null;
        }

        $query = AiApiKey::where('provider_id', $provider->id)
            ->where('is_active', true);

        // If preferred key is specified, try to use it
        if ($preferredKeyId) {
            $preferredKey = $query->where('id', $preferredKeyId)->first();
            if ($preferredKey && $preferredKey->isWithinRateLimit()) {
                return $preferredKey;
            }
        }

        // Otherwise, get the highest priority key that's within rate limits
        return $query->orderBy('priority', 'desc')
            ->get()
            ->first(fn($key) => $key->isWithinRateLimit());
    }

    /**
     * Get random active API key for a provider
     */
    public function getRandomApiKey(string $providerSlug): ?AiApiKey
    {
        $provider = AiProvider::where('slug', $providerSlug)->first();
        
        if (!$provider) {
            return null;
        }

        return AiApiKey::where('provider_id', $provider->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->get()
            ->first(fn($key) => $key->isWithinRateLimit());
    }

    /**
     * Enable/disable an API key
     */
    public function toggleApiKey(AiApiKey $apiKey, bool $isActive): AiApiKey
    {
        $apiKey->update(['is_active' => $isActive]);
        return $apiKey->fresh();
    }
}
