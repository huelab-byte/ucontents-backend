<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AiIntegration\Services\AiProviderService;

/**
 * Seeder to initialize AI providers
 */
class AiProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = app(AiProviderService::class);
        $service->initializeProviders();
    }
}
