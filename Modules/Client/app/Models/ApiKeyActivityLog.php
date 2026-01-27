<?php

declare(strict_types=1);

namespace Modules\Client\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * API Key Activity Log Model
 * 
 * Logs all API key usage for auditing and monitoring.
 */
class ApiKeyActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key_id',
        'api_client_id',
        'endpoint',
        'method',
        'status_code',
        'ip_address',
        'user_agent',
        'request_data',
        'response_data',
        'response_time_ms',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'response_time_ms' => 'integer',
    ];

    /**
     * Get the API key that was used
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Get the API client
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }
}
