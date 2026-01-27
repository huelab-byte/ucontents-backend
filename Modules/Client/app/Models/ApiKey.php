<?php

declare(strict_types=1);

namespace Modules\Client\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

/**
 * API Key Model
 * 
 * Represents an API key pair (public key + secret) for an API client.
 */
class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_client_id',
        'name',
        'public_key',
        'secret_key',
        'key_hash',
        'is_active',
        'last_used_at',
        'expires_at',
        'rotated_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'rotated_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_key',
        'key_hash',
    ];

    /**
     * Get the API client that owns this key
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Get activity logs for this API key
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ApiKeyActivityLog::class);
    }

    /**
     * Set the secret key (encrypt it)
     */
    public function setSecretKeyAttribute(?string $value): void
    {
        if ($value !== null) {
            $this->attributes['secret_key'] = encrypt($value);
        }
    }

    /**
     * Get the decrypted secret key
     */
    public function getSecretKeyAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if key is active and not revoked/expired
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->revoked_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if key needs rotation (90 days)
     */
    public function needsRotation(): bool
    {
        if (!$this->rotated_at) {
            return $this->created_at->addDays(90)->isPast();
        }

        return $this->rotated_at->addDays(90)->isPast();
    }
}
