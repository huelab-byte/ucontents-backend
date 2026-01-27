<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Actions;

use Modules\SocialConnection\Models\SocialProviderApp;

class FindOrCreateProviderAppAction
{
    private const ALLOWED_PROVIDERS = ['meta', 'google', 'tiktok'];

    public function execute(string $provider): SocialProviderApp
    {
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404);
        }

        return SocialProviderApp::firstOrCreate(['provider' => $provider], [
            'enabled' => false,
            'scopes' => [],
            'extra' => [],
        ]);
    }

    public function ensureAllProvidersExist(): void
    {
        foreach (self::ALLOWED_PROVIDERS as $provider) {
            SocialProviderApp::firstOrCreate(['provider' => $provider], [
                'enabled' => false,
                'scopes' => [],
                'extra' => [],
            ]);
        }
    }
}
