<?php

declare(strict_types=1);

namespace Modules\ProxySetup\DTOs;

readonly class UpdateProxySettingsDTO
{
    public function __construct(
        public ?bool $useRandomProxy = null,
        public ?bool $applyToAllChannels = null,
        public ?string $onProxyFailure = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            useRandomProxy: $data['use_random_proxy'] ?? null,
            applyToAllChannels: $data['apply_to_all_channels'] ?? null,
            onProxyFailure: $data['on_proxy_failure'] ?? null,
        );
    }
}
