<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Console\Commands;

use Illuminate\Console\Command;
use Modules\FootageLibrary\Integrations\QdrantService;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Actions\StoreEmbeddingAction;

class RebuildQdrantCollection extends Command
{
    protected $signature = 'footage:rebuild-qdrant 
                            {--delete-collection : Delete and recreate the collection}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Rebuild Qdrant collection by re-indexing all footage with valid embeddings';

    public function __construct(
        private QdrantService $qdrantService,
        private StoreEmbeddingAction $storeEmbeddingAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Qdrant Collection Rebuild Tool');
        $this->line('');

        // Get collection info
        $info = $this->qdrantService->getCollectionInfo();
        if ($info) {
            $this->info('Current collection info:');
            $this->line("  - Points: " . ($info['points_count'] ?? 'unknown'));
            $this->line("  - Vector size: " . ($info['config']['params']['vectors']['size'] ?? 'unknown'));
            $this->line('');
        }

        // Option 1: Delete and recreate collection
        if ($this->option('delete-collection')) {
            if (!$this->option('force') && !$this->confirm('This will DELETE all points in the collection. Continue?')) {
                $this->info('Cancelled.');
                return 0;
            }

            $this->warn('Deleting collection...');
            if ($this->qdrantService->deleteCollection()) {
                $this->info('Collection deleted.');
            } else {
                $this->error('Failed to delete collection.');
            }

            $this->info('Creating new collection...');
            if ($this->qdrantService->createCollection()) {
                $this->info('Collection created.');
            } else {
                $this->error('Failed to create collection.');
                return 1;
            }

            // Clear all embedding_ids in database
            $this->info('Clearing embedding IDs from footage records...');
            Footage::whereNotNull('embedding_id')->update(['embedding_id' => null]);
        }

        // Re-index all footage
        $this->info('');
        $this->info('Re-indexing footage with AI metadata...');
        
        // Only index footage that has AI-generated metadata (not 'none')
        $footage = Footage::where('status', 'ready')
            ->where(function ($query) {
                $query->whereNull('metadata->ai_metadata_source')
                    ->orWhere('metadata->ai_metadata_source', '!=', 'none');
            })
            ->get();

        $this->info("Found {$footage->count()} footage items to index.");

        if ($footage->isEmpty()) {
            $this->info('No footage to index.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm("Re-index {$footage->count()} items?")) {
            $this->info('Cancelled.');
            return 0;
        }

        $bar = $this->output->createProgressBar($footage->count());
        $bar->start();

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($footage as $item) {
            try {
                // Skip if no meaningful text to embed
                $description = $item->metadata['description'] ?? '';
                $tags = $item->metadata['tags'] ?? [];
                
                if (empty($description) && empty($tags)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $this->storeEmbeddingAction->execute($item);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("  Failed: {$item->id} - {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Completed:");
        $this->line("  - Success: {$success}");
        $this->line("  - Failed: {$failed}");
        $this->line("  - Skipped (no metadata): {$skipped}");

        return $failed > 0 ? 1 : 0;
    }
}
