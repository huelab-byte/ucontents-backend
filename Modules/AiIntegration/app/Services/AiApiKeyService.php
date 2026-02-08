<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Services;

use Illuminate\Support\Facades\Log;
use Modules\AiIntegration\DTOs\CreateApiKeyDTO;
use Modules\AiIntegration\DTOs\UpdateApiKeyDTO;
use Modules\AiIntegration\Models\AiApiKey;
use Modules\AiIntegration\Models\AiProvider;
use Modules\UserManagement\Models\User;

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
            'user_id' => $dto->userId,
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
            'scopes' => $dto->scopes ?? null,
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

        // Handle scopes separately as it can be an empty array (which should be allowed)
        if (property_exists($dto, 'scopes')) {
            $updateData['scopes'] = $dto->scopes;
        }

        $apiKey->update($updateData);

        return $apiKey->fresh();
    }

    /**
     * Get best available API key for a provider (backward compatible - no scope filtering)
     * 
     * @param string $providerSlug Provider slug
     * @param int|null $preferredKeyId Preferred API key ID (if specified)
     * @return AiApiKey|null
     */
    /**
     * Get best available API key for a provider (backward compatible - no scope filtering)
     * 
     * @param string $providerSlug Provider slug
     * @param int|null $preferredKeyId Preferred API key ID (if specified)
     * @param int|null $userId User ID to find personal keys for
     * @return AiApiKey|null
     */
    public function getBestApiKey(string $providerSlug, ?int $preferredKeyId = null, ?int $userId = null): ?AiApiKey
    {
        return $this->getBestApiKeyForScope($providerSlug, null, $preferredKeyId, $userId);
    }

    /**
     * Get best available API key for a provider filtered by scope.
     *
     * When a customer has configured their own API key (user_id set on the key), that key
     * is always used for their requests. Admin/system keys (user_id = null) are used only
     * when the user has no key for that provider or as fallback.
     *
     * Prioritization:
     * 1. Customer keys first: if userId is provided, use that user's keys (user_id = userId).
     * 2. Preferred Key ID (if specified and in the candidate set).
     * 3. System keys: only if no suitable customer key found (admin-configured keys, user_id = null).
     *
     * @param string $providerSlug Provider slug
     * @param string|null $scope The scope to filter by (e.g., 'vision_content', 'embedding')
     * @param int|null $preferredKeyId Preferred API key ID (if specified)
     * @param int|null $userId User ID for the request; when set, their keys are preferred over system keys
     * @return AiApiKey|null
     */
    public function getBestApiKeyForScope(string $providerSlug, ?string $scope = null, ?int $preferredKeyId = null, ?int $userId = null): ?AiApiKey
    {
        $provider = AiProvider::where('slug', $providerSlug)->first();
        
        if (!$provider) {
            Log::debug('AI provider not found', ['provider_slug' => $providerSlug]);
            return null;
        }

        // Base query for Active Keys
        $baseQuery = AiApiKey::where('provider_id', $provider->id)
            ->where('is_active', true);

        // --- STRATEGY 1: Check USER keys first ---
        if ($userId) {
            $userKeys = (clone $baseQuery)
                ->where('user_id', $userId)
                ->orderBy('priority', 'desc')
                ->get();

            // Try to find a suitable user key
            $bestUserKey = $this->findBestKeyFromCollection($userKeys, $scope, $preferredKeyId);
            
            if ($bestUserKey) {
                Log::debug('Using customer personal API key', [
                    'user_id' => $userId, 
                    'key_id' => $bestUserKey->id
                ]);
                return $bestUserKey;
            }
        }

        // --- STRATEGY 2: Fallback to SYSTEM keys (user_id = null, i.e. admin-configured shared keys) ---
        $systemKeys = (clone $baseQuery)
            ->whereNull('user_id')
            ->orderBy('priority', 'desc')
            ->get();

        $bestSystemKey = $this->findBestKeyFromCollection($systemKeys, $scope, $preferredKeyId);
        if ($bestSystemKey) {
            Log::debug('Using system API key', ['key_id' => $bestSystemKey->id, 'scope' => $scope]);
            return $bestSystemKey;
        }

        // --- STRATEGY 3: Fallback to keys owned by users with manage_ai_api_keys (admin panel keys created before user_id was forced to null) ---
        $adminUserIds = $this->getUserIdsWithManageAiApiKeys();
        if ($adminUserIds !== []) {
            $adminKeys = (clone $baseQuery)
                ->whereIn('user_id', $adminUserIds)
                ->orderBy('priority', 'desc')
                ->get();
            $bestAdminKey = $this->findBestKeyFromCollection($adminKeys, $scope, $preferredKeyId);
            if ($bestAdminKey) {
                Log::debug('Using admin-owned API key as fallback', ['key_id' => $bestAdminKey->id, 'scope' => $scope]);
                return $bestAdminKey;
            }
        }

        Log::debug('No active system API keys found for provider', ['provider_slug' => $providerSlug]);
        return null;
    }

    /**
     * User IDs that have manage_ai_api_keys (so their keys can be used as system fallback for customers).
     */
    private function getUserIdsWithManageAiApiKeys(): array
    {
        return User::whereHas('roles.permissions', function ($query) {
            $query->where('slug', 'manage_ai_api_keys');
        })->pluck('id')->toArray();
    }

    /**
     * Helper to find the best key from a collection based on scope, preference, and limits
     */
    private function findBestKeyFromCollection($keys, ?string $scope, ?int $preferredId): ?AiApiKey
    {
        if ($keys->isEmpty()) return null;

        // 1. Try preferred key if it exists in this collection
        if ($preferredId) {
            $preferred = $keys->firstWhere('id', $preferredId);
            if ($preferred && $preferred->supportsScope($scope) && $preferred->isWithinRateLimit()) {
                return $preferred;
            }
        }

        // 2. Try Scoped Keys (keys that explicitly have this scope)
        if ($scope !== null && $scope !== 'general') {
            $scopedKey = $keys->first(function ($key) use ($scope) {
                return !empty($key->scopes) 
                    && in_array($scope, $key->scopes, true) 
                    && $key->isWithinRateLimit();
            });
            if ($scopedKey) return $scopedKey;
        }

        // 3. Fallback to any compatible key (Universal or Scoped)
        return $keys->first(function ($key) use ($scope) {
            return $key->supportsScope($scope) && $key->isWithinRateLimit();
        });
    }

    /**
     * Get random active API key for a provider (backward compatible - no scope filtering)
     */
    public function getRandomApiKey(string $providerSlug): ?AiApiKey
    {
        return $this->getRandomApiKeyForScope($providerSlug, null);
    }

    /**
     * Get random active API key for a provider filtered by scope.
     * 
     * @param string $providerSlug Provider slug
     * @param string|null $scope The scope to filter by
     * @return AiApiKey|null
     */
    public function getRandomApiKeyForScope(string $providerSlug, ?string $scope = null): ?AiApiKey
    {
        $provider = AiProvider::where('slug', $providerSlug)->first();
        
        if (!$provider) {
            return null;
        }

        $allKeys = AiApiKey::where('provider_id', $provider->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->get();

        // Filter by scope and rate limit
        return $allKeys->first(function ($key) use ($scope) {
            return $key->supportsScope($scope) && $key->isWithinRateLimit();
        });
    }

    /**
     * Enable/disable an API key
     */
    public function toggleApiKey(AiApiKey $apiKey, bool $isActive): AiApiKey
    {
        $apiKey->update(['is_active' => $isActive]);
        return $apiKey->fresh();
    }

    /**
     * Get all available scopes from configuration
     * 
     * @return array
     */
    public function getAvailableScopes(): array
    {
        return config('aiintegration.module.scopes', []);
    }

    /**
     * Update scopes for an API key
     * 
     * @param AiApiKey $apiKey
     * @param array|null $scopes Array of scope slugs, or null/empty for all scopes
     * @return AiApiKey
     */
    public function updateScopes(AiApiKey $apiKey, ?array $scopes): AiApiKey
    {
        $apiKey->update(['scopes' => $scopes ?: null]);
        return $apiKey->fresh();
    }
}

