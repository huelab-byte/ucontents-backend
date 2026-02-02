<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Policies;

use Modules\MediaUpload\Models\CaptionTemplate;
use Modules\UserManagement\Models\User;

class CaptionTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_caption_templates');
    }

    public function view(User $user, CaptionTemplate $template): bool
    {
        return $template->user_id === $user->id && $user->hasPermission('manage_caption_templates');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_caption_templates');
    }

    public function update(User $user, CaptionTemplate $template): bool
    {
        return $template->user_id === $user->id && $user->hasPermission('manage_caption_templates');
    }

    public function delete(User $user, CaptionTemplate $template): bool
    {
        return $template->user_id === $user->id && $user->hasPermission('manage_caption_templates');
    }
}
