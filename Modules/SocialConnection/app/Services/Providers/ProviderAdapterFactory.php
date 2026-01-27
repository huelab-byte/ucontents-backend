<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Services\Providers;

use Modules\SocialConnection\Services\Providers\Adapters\GoogleAdapter;
use Modules\SocialConnection\Services\Providers\Adapters\MetaAdapter;
use Modules\SocialConnection\Services\Providers\Adapters\TikTokAdapter;

class ProviderAdapterFactory
{
    public function make(string $provider): ProviderAdapterInterface
    {
        return match ($provider) {
            'meta' => new MetaAdapter(),
            'google' => new GoogleAdapter(),
            'tiktok' => new TikTokAdapter(),
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
        };
    }
}

