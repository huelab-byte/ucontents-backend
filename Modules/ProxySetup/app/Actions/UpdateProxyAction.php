<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Actions;

use Modules\ProxySetup\DTOs\UpdateProxyDTO;
use Modules\ProxySetup\Models\Proxy;

class UpdateProxyAction
{
    public function execute(Proxy $proxy, UpdateProxyDTO $dto): Proxy
    {
        $data = [];

        if ($dto->name !== null) {
            $data['name'] = $dto->name;
        }

        if ($dto->type !== null) {
            $data['type'] = $dto->type;
        }

        if ($dto->host !== null) {
            $data['host'] = $dto->host;
        }

        if ($dto->port !== null) {
            $data['port'] = $dto->port;
        }

        if ($dto->isEnabled !== null) {
            $data['is_enabled'] = $dto->isEnabled;
        }

        // Handle username: update if provided, clear if explicitly set to empty
        if ($dto->clearUsername) {
            $data['username'] = null;
        } elseif ($dto->username !== null) {
            $data['username'] = $dto->username;
        }

        // Handle password: update if provided, clear if explicitly set to empty
        if ($dto->clearPassword) {
            $data['password'] = null;
        } elseif ($dto->password !== null) {
            $data['password'] = $dto->password;
        }

        if (!empty($data)) {
            $proxy->update($data);
        }

        return $proxy->fresh();
    }
}
