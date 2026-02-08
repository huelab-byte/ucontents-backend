<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Console\Commands;

use Illuminate\Console\Command;
use Modules\AiIntegration\Actions\TestApiKeyAction;
use Modules\AiIntegration\Models\AiApiKey;

/**
 * Test an AI API key by ID (e.g. Azure OpenAI key).
 * Run: php artisan ai:test-key {id}
 * Or without id to list active keys and optionally test one.
 */
class TestApiKeyCommand extends Command
{
    protected $signature = 'ai:test-key {id? : API key ID to test}';

    protected $description = 'Test an AI API key by making a simple call. Use id to test a specific key, or run without id to list keys.';

    public function handle(TestApiKeyAction $testAction): int
    {
        $id = $this->argument('id');

        if ($id === null) {
            $keys = AiApiKey::with('provider')->where('is_active', true)->orderBy('provider_id')->orderBy('id')->get();
            if ($keys->isEmpty()) {
                $this->warn('No active API keys found. Add a key in Admin → Settings → AI Integration.');
                return self::FAILURE;
            }
            $this->table(
                ['ID', 'Name', 'Provider', 'user_id'],
                $keys->map(fn (AiApiKey $k) => [
                    $k->id,
                    $k->name,
                    $k->provider?->slug ?? '?',
                    $k->user_id ?? 'null (system)',
                ])
            );
            $this->line('Run: php artisan ai:test-key <ID> to test a key.');
            return self::SUCCESS;
        }

        $apiKey = AiApiKey::with('provider')->find($id);
        if (!$apiKey) {
            $this->error("API key with id {$id} not found.");
            return self::FAILURE;
        }

        $this->info("Testing key: {$apiKey->name} (provider: " . ($apiKey->provider?->slug ?? '?') . ")");
        if ($apiKey->provider?->slug === 'azure_openai') {
            $dep = $apiKey->metadata['deployment_name'] ?? '(not set)';
            $this->line("  Deployment name: {$dep}");
        }

        $result = $testAction->execute($apiKey);

        if ($result['success']) {
            $this->info('OK - ' . ($result['message'] ?? '') . ' (' . ($result['response_time_ms'] ?? 0) . 'ms)');
            return self::SUCCESS;
        }

        $this->error('Failed: ' . ($result['error'] ?? $result['message'] ?? 'Unknown error'));
        return self::FAILURE;
    }
}
