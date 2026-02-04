<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Adapters;

use Modules\AiIntegration\Models\AiProvider;

/**
 * Factory for creating provider adapters
 */
class AdapterFactory
{
    /**
     * Create adapter for a provider
     *
     * @param AiProvider $provider
     * @return ProviderAdapterInterface
     * @throws \Exception
     */
    public function create(AiProvider $provider): ProviderAdapterInterface
    {
        return match ($provider->slug) {
            'openai', 'azure_openai', 'deepseek', 'xai' => new OpenAiAdapter(),
            'anthropic' => new AnthropicAdapter(),
            'google' => new GoogleGeminiAdapter(),
            'ucontents' => new UcontentsAdapter(),
            default => throw new \Exception("Unsupported provider: {$provider->slug}"),
        };
    }
}
