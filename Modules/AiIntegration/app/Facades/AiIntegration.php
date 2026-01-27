<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for AI Integration module
 * 
 * @method static \Modules\AiIntegration\Services\AiModelCallService callModel()
 * @method static \Modules\AiIntegration\Services\AiApiKeyService getApiKey()
 * @method static \Modules\AiIntegration\Services\AiProviderService getProvider()
 */
class AiIntegration extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'aiintegration';
    }
}
