<?php

declare(strict_types=1);

namespace Modules\Client\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Client\Models\ApiKey;

/**
 * Service for API key generation and management
 */
class ApiKeyService
{
    /**
     * Generate a new API key pair
     * 
     * Format: {prefix}_{environment}_{random}
     * - Public key: pk_prod_xxxxx (32 chars random)
     * - Secret key: sk_prod_xxxxx (64 chars random)
     */
    public function generateKeyPair(string $environment = 'production'): array
    {
        $prefix = match ($environment) {
            'production' => 'prod',
            'staging' => 'staging',
            'development' => 'dev',
            default => 'prod',
        };

        $publicKey = 'pk_' . $prefix . '_' . Str::random(32);
        $secretKey = 'sk_' . $prefix . '_' . Str::random(64);

        return [
            'public_key' => $publicKey,
            'secret_key' => $secretKey,
        ];
    }

    /**
     * Create and store an API key
     */
    public function createApiKey(
        int $apiClientId,
        string $environment = 'production',
        ?string $name = null,
        ?\DateTimeInterface $expiresAt = null
    ): ApiKey {
        $keyPair = $this->generateKeyPair($environment);

        $apiKey = new ApiKey();
        $apiKey->api_client_id = $apiClientId;
        $apiKey->name = $name;
        $apiKey->public_key = $keyPair['public_key'];
        $apiKey->key_hash = Hash::make($keyPair['public_key']); // Hash for lookup
        $apiKey->secret_key = $keyPair['secret_key']; // This will be encrypted via mutator
        $apiKey->is_active = true;
        $apiKey->expires_at = $expiresAt;
        $apiKey->save();

        return $apiKey;
    }

    /**
     * Find API key by public key
     */
    public function findByPublicKey(string $publicKey): ?ApiKey
    {
        return ApiKey::where('public_key', $publicKey)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Validate API key and secret
     */
    public function validateKeyPair(string $publicKey, string $secretKey): ?ApiKey
    {
        $apiKey = $this->findByPublicKey($publicKey);

        if (!$apiKey) {
            return null;
        }

        // Verify secret key matches
        if ($apiKey->secret_key !== $secretKey) {
            return null;
        }

        return $apiKey;
    }

    /**
     * Rotate an API key (create new, mark old as rotated)
     */
    public function rotateApiKey(ApiKey $apiKey): ApiKey
    {
        // Create new key pair
        $newKey = $this->createApiKey(
            apiClientId: $apiKey->api_client_id,
            environment: $apiKey->apiClient->environment,
            name: $apiKey->name . ' (Rotated)',
            expiresAt: $apiKey->expires_at
        );

        // Mark old key as rotated
        $apiKey->rotated_at = now();
        $apiKey->is_active = false;
        $apiKey->save();

        return $newKey;
    }

    /**
     * Revoke an API key
     */
    public function revokeApiKey(ApiKey $apiKey, ?string $reason = null): void
    {
        $apiKey->revoked_at = now();
        $apiKey->revoked_reason = $reason;
        $apiKey->is_active = false;
        $apiKey->save();
    }
}
