<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialProviderApp;

class UpdateProviderAppAction
{
    public function execute(SocialProviderApp $app, array $data): SocialProviderApp
    {
        // Keep secrets if not provided
        if (array_key_exists('client_secret', $data) && empty($data['client_secret'])) {
            unset($data['client_secret']);
        }

        $app->fill($data);
        $app->save();

        return $app->fresh();
    }
}
