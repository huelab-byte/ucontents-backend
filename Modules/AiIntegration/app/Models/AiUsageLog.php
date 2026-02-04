<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

/**
 * AI Usage Log Model
 * 
 * Tracks all AI API calls for analytics and billing
 */
class AiUsageLog extends Model
{
    use HasFactory;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_RATE_LIMITED = 'rate_limited';

    public const STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_ERROR,
        self::STATUS_RATE_LIMITED,
    ];

    protected $fillable = [
        'api_key_id',
        'user_id',
        'provider_slug',
        'model',
        'prompt',
        'response',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'response_time_ms',
        'status',
        'error_message',
        'module',
        'feature',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'cost' => 'decimal:6',
            'response_time_ms' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the API key used for this request
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(AiApiKey::class, 'api_key_id');
    }

    /**
     * Get the user who made this request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
