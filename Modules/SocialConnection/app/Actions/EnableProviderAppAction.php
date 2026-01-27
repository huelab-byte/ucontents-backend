<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialProviderApp;

class EnableProviderAppAction
{
    public function execute(SocialProviderApp $app): SocialProviderApp
    {
        $app->update(['enabled' => true]);

        return $app->fresh();
    }
}
