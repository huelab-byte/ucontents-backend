<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Console\Commands;

use Illuminate\Console\Command;
use Modules\UserManagement\Models\Permission;

/**
 * Seed AI Integration permissions
 */
class SeedAiIntegrationPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-integration:seed-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed AI Integration permissions to the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get permissions from Core module config
        $permissionsPath = base_path('Modules/Core/config/permissions.php');
        $allPermissions = file_exists($permissionsPath) ? require $permissionsPath : [];

        // Filter only AI Integration permissions
        $aiPermissions = [
            'manage_ai_providers' => $allPermissions['manage_ai_providers'] ?? 'Full access to AI provider management',
            'manage_ai_api_keys' => $allPermissions['manage_ai_api_keys'] ?? 'Full access to AI API key management',
            'view_ai_usage' => $allPermissions['view_ai_usage'] ?? 'View AI usage statistics and logs',
            'manage_prompt_templates' => $allPermissions['manage_prompt_templates'] ?? 'Full access to prompt template management',
            'call_ai_models' => $allPermissions['call_ai_models'] ?? 'Call AI models (customer)',
            'use_prompt_templates' => $allPermissions['use_prompt_templates'] ?? 'Use prompt templates (customer)',
        ];

        $created = 0;
        $updated = 0;

        foreach ($aiPermissions as $slug => $description) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => 'AiIntegration',
                ]
            );

            if ($permission->wasRecentlyCreated) {
                $created++;
            } else {
                // Update description if it changed
                if ($permission->description !== $description) {
                    $permission->update(['description' => $description]);
                    $updated++;
                }
            }
        }

        $this->info("Successfully seeded AI Integration permissions:");
        $this->info("  - Created: {$created}");
        $this->info("  - Updated: {$updated}");
        $this->info("  - Total: " . count($aiPermissions));

        return Command::SUCCESS;
    }
}
