<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Actions;

use Modules\ProxySetup\DTOs\UpdateProxySettingsDTO;
use Modules\ProxySetup\Models\ProxySetting;
use Modules\UserManagement\Models\User;

class UpdateProxySettingsAction
{
    public function execute(User $user, UpdateProxySettingsDTO $dto): ProxySetting
    {
        $settings = ProxySetting::getOrCreateForUser($user->id);

        $data = [];

        if ($dto->useRandomProxy !== null) {
            $data['use_random_proxy'] = $dto->useRandomProxy;
        }

        if ($dto->applyToAllChannels !== null) {
            $data['apply_to_all_channels'] = $dto->applyToAllChannels;
        }

        if ($dto->onProxyFailure !== null) {
            $data['on_proxy_failure'] = $dto->onProxyFailure;
        }

        if (!empty($data)) {
            $settings->update($data);
        }

        return $settings->fresh();
    }
}
