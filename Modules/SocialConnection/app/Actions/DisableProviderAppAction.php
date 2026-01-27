<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialProviderApp;

class DisableProviderAppAction
{
    public function execute(SocialProviderApp $app): SocialProviderApp
    {
        $app->update(['enabled' => false]);

        return $app->fresh();
    }
}
