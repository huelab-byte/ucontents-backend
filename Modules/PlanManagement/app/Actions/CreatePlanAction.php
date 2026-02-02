<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Actions;

use Modules\PlanManagement\DTOs\CreatePlanDTO;
use Modules\PlanManagement\Models\Plan;

class CreatePlanAction
{
    public function execute(CreatePlanDTO $dto): Plan
    {
        return Plan::create([
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
            'ai_usage_limit' => $dto->aiUsageLimit,
            'max_file_upload' => $dto->maxFileUpload,
            'total_storage_bytes' => $dto->totalStorageBytes,
            'features' => $dto->features,
            'max_connections' => $dto->maxConnections,
            'monthly_post_limit' => $dto->monthlyPostLimit,
            'subscription_type' => $dto->subscriptionType,
            'price' => $dto->price,
            'currency' => $dto->currency,
            'is_active' => $dto->isActive,
            'sort_order' => $dto->sortOrder,
            'featured' => $dto->featured,
            'is_free_plan' => $dto->isFreePlan,
            'trial_days' => $dto->trialDays,
        ]);
    }
}
