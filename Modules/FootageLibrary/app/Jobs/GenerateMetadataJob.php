<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Actions\GenerateMetadataFromTitleAction;
use Modules\FootageLibrary\Jobs\StoreEmbeddingJob;
use Illuminate\Support\Facades\Log;

class GenerateMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $footageId
    ) {}

    public function handle(GenerateMetadataFromTitleAction $generateMetadataAction): void
    {
        $footage = Footage::find($this->footageId);
        
        if (!$footage) {
            Log::error('Footage not found for metadata generation', ['footage_id' => $this->footageId]);
            return;
        }

        try {
            $footage->update(['status' => 'processing']);

            // Generate metadata from title
            $metadata = $generateMetadataAction->execute($footage->title, $footage->user_id);
            
            // Update footage metadata
            $existingMetadata = $footage->metadata;
            $updatedMetadata = array_merge($existingMetadata, $metadata);
            
            $footage->update([
                'metadata' => $updatedMetadata,
                'status' => 'ready',
            ]);

            // Dispatch job to store embedding
            StoreEmbeddingJob::dispatch($footage->id);
        } catch (\Exception $e) {
            $footage->update(['status' => 'failed']);
            
            Log::error('Metadata generation job failed', [
                'footage_id' => $this->footageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
