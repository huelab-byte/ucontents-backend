<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PlanManagement\Models\Plan;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'ai_usage_limit' => $this->ai_usage_limit,
            'max_file_upload' => $this->max_file_upload,
            'total_storage_bytes' => $this->total_storage_bytes,
            'features' => $this->features,
            'max_connections' => $this->max_connections,
            'monthly_post_limit' => $this->monthly_post_limit,
            'subscription_type' => $this->subscription_type,
            'price' => $this->price,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'featured' => $this->featured ?? false,
            'is_free_plan' => $this->is_free_plan ?? false,
            'trial_days' => $this->trial_days,
            'is_lifetime' => $this->isLifetime(),
            'is_recurring' => $this->isRecurring(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
