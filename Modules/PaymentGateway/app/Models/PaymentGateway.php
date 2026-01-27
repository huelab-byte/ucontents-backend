<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment Gateway Model
 * 
 * Represents a configured payment gateway (Stripe, PayPal, etc.)
 */
class PaymentGateway extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'is_active',
        'is_test_mode',
        'credentials',
        'settings',
        'description',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_test_mode' => 'boolean',
        'credentials' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the user who created this gateway
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get all payments using this gateway
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all subscriptions using this gateway
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all refunds using this gateway
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Check if gateway is active and ready to use
     */
    public function isReady(): bool
    {
        return $this->is_active && !empty($this->credentials);
    }
}
