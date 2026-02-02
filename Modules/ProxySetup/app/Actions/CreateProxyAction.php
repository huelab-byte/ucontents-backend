<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Actions;

use Modules\ProxySetup\DTOs\CreateProxyDTO;
use Modules\ProxySetup\Models\Proxy;
use Modules\UserManagement\Models\User;

class CreateProxyAction
{
    public function execute(User $user, CreateProxyDTO $dto): Proxy
    {
        return Proxy::create([
            'user_id' => $user->id,
            'name' => $dto->name,
            'type' => $dto->type,
            'host' => $dto->host,
            'port' => $dto->port,
            'username' => $dto->username,
            'password' => $dto->password,
            'is_enabled' => $dto->isEnabled,
        ]);
    }
}
