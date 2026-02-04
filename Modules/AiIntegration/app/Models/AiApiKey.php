<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * AI API Key Model
 * 
 * Represents an API key for an AI provider
 */
class AiApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'user_id',
        'name',
        'api_key',
        'api_secret',
        'endpoint_url',
        'organization_id',
        'project_id',
        'is_active',
        'priority',
        'rate_limit_per_minute',
        'rate_limit_per_day',
        'metadata',
        'scopes',
        'last_used_at',
        'total_requests',
        'total_tokens',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'rate_limit_per_minute' => 'integer',
            'rate_limit_per_day' => 'integer',
            'metadata' => 'array',
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'total_requests' => 'integer',
            'total_tokens' => 'integer',
        ];
    }

    /**
     * Check if this API key supports a specific scope.
     * If no scopes are defined, the key is considered universal (supports all scopes).
     *
     * @param string|null $scope The scope to check, or null for general use
     * @return bool
     */
    public function supportsScope(?string $scope): bool
    {
        // If no scopes defined, the key can be used for any scope
        if (empty($this->scopes)) {
            return true;
        }

        // If no specific scope requested, any scoped key can handle it
        if ($scope === null || $scope === 'general') {
            return true;
        }

        // Check if the requested scope is in the key's scope list
        return in_array($scope, $this->scopes, true);
    }

    /**
     * Get the list of scope names for this API key.
     *
     * @return array
     */
    public function getScopeNames(): array
    {
        if (empty($this->scopes)) {
            return ['All scopes'];
        }

        $scopeConfig = config('aiintegration.module.scopes', []);
        $names = [];

        foreach ($this->scopes as $scope) {
            $names[] = $scopeConfig[$scope]['name'] ?? ucfirst($scope);
        }

        return $names;
    }

    /**
     * Get the provider that owns this API key
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    /**
     * Get the user that owns this API key (if any)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Models\User::class, 'user_id');
    }

    /**
     * Get usage logs for this API key
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class, 'api_key_id');
    }

    /**
     * Get decrypted API key
     */
    public function getDecryptedApiKey(): string
    {
        try {
            return Crypt::decryptString($this->api_key);
        } catch (\Exception $e) {
            // If decryption fails, assume it's stored in plain text (for migration)
            return $this->api_key;
        }
    }

    /**
     * Set encrypted API key
     */
    public function setApiKeyAttribute(string $value): void
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted API secret
     */
    public function getDecryptedApiSecret(): ?string
    {
        if (!$this->api_secret) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_secret);
        } catch (\Exception $e) {
            return $this->api_secret;
        }
    }

    /**
     * Set encrypted API secret
     */
    public function setApiSecretAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['api_secret'] = Crypt::encryptString($value);
        } else {
            $this->attributes['api_secret'] = null;
        }
    }

    /**
     * Mark API key as used
     */
    public function markAsUsed(int $tokens = 0): void
    {
        $this->update([
            'last_used_at' => now(),
            'total_requests' => $this->total_requests + 1,
            'total_tokens' => $this->total_tokens + $tokens,
        ]);
    }

    /**
     * Check if API key is within rate limits
     */
    public function isWithinRateLimit(): bool
    {
        // Check per-minute limit
        if ($this->rate_limit_per_minute) {
            $recentRequests = $this->usageLogs()
                ->where('created_at', '>=', now()->subMinute())
                ->count();
            
            if ($recentRequests >= $this->rate_limit_per_minute) {
                return false;
            }
        }

        // Check per-day limit
        if ($this->rate_limit_per_day) {
            $todayRequests = $this->usageLogs()
                ->whereDate('created_at', today())
                ->count();
            
            if ($todayRequests >= $this->rate_limit_per_day) {
                return false;
            }
        }

        return true;
    }
}
