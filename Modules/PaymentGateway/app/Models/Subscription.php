<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Subscription Model
 * 
 * Represents a recurring subscription (weekly, monthly, yearly)
 */
class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subscription_number',
        'user_id',
        'subscriptionable_type',
        'subscriptionable_id',
        'name',
        'interval',
        'amount',
        'currency',
        'status',
        'start_date',
        'end_date',
        'next_billing_date',
        'last_payment_date',
        'payment_gateway_id',
        'gateway_subscription_id',
        'gateway_data',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'last_payment_date' => 'date',
        'gateway_data' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user who owns this subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the payment gateway used
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    /**
     * Get the parent subscriptionable model (package, service, etc.)
     */
    public function subscriptionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if subscription is due for billing
     */
    public function isDueForBilling(): bool
    {
        return $this->isActive() 
            && $this->next_billing_date 
            && $this->next_billing_date->isPast();
    }
}
