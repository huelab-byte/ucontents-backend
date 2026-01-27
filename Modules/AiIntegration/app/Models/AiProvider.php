<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AI Provider Model
 * 
 * Represents an AI service provider (OpenAI, Azure, Anthropic, etc.)
 */
class AiProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'supported_models',
        'base_url',
        'is_active',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'supported_models' => 'array',
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    /**
     * Get all API keys for this provider
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(AiApiKey::class, 'provider_id');
    }

    /**
     * Get active API keys for this provider
     */
    public function activeApiKeys(): HasMany
    {
        return $this->apiKeys()->where('is_active', true);
    }

    /**
     * Check if provider has any active API keys
     */
    public function hasActiveKeys(): bool
    {
        return $this->activeApiKeys()->exists();
    }
}
