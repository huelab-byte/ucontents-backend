<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Policies;

use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\UserManagement\Models\User;

class BulkPostingCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_bulk_posting_campaigns');
    }

    public function view(User $user, BulkPostingCampaign $campaign): bool
    {
        return $user->hasPermission('view_bulk_posting_campaigns')
            && $campaign->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_bulk_posting_campaigns');
    }

    public function update(User $user, BulkPostingCampaign $campaign): bool
    {
        return $user->hasPermission('manage_bulk_posting_campaigns')
            && $campaign->user_id === $user->id;
    }

    public function delete(User $user, BulkPostingCampaign $campaign): bool
    {
        return $user->hasPermission('manage_bulk_posting_campaigns')
            && $campaign->user_id === $user->id;
    }
}
