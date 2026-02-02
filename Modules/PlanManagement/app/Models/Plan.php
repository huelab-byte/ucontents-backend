<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'ai_usage_limit',
        'max_file_upload',
        'total_storage_bytes',
        'features',
        'max_connections',
        'monthly_post_limit',
        'subscription_type',
        'price',
        'currency',
        'is_active',
        'sort_order',
        'featured',
        'is_free_plan',
        'trial_days',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'featured' => 'boolean',
        'is_free_plan' => 'boolean',
    ];

    /**
     * Subscriptions linked to this plan (PaymentGateway Subscription model).
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(\Modules\PaymentGateway\Models\Subscription::class, 'subscriptionable');
    }

    public function isLifetime(): bool
    {
        return $this->subscription_type === 'lifetime';
    }

    public function isRecurring(): bool
    {
        return in_array($this->subscription_type, ['weekly', 'monthly', 'yearly'], true);
    }

    public function isFreePlan(): bool
    {
        return (bool) $this->is_free_plan;
    }

    public function hasTrial(): bool
    {
        return $this->trial_days !== null && $this->trial_days > 0;
    }

    /**
     * Map subscription_type to PaymentGateway interval (weekly, monthly, yearly).
     */
    public function getIntervalForGateway(): ?string
    {
        return match ($this->subscription_type) {
            'weekly' => 'weekly',
            'monthly' => 'monthly',
            'yearly' => 'yearly',
            'lifetime' => null,
            default => null,
        };
    }
}
