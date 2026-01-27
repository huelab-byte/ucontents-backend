<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Actions\StoreEmbeddingAction;
use Illuminate\Support\Facades\Log;

class StoreEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $footageId
    ) {}

    public function handle(StoreEmbeddingAction $storeEmbeddingAction): void
    {
        $footage = Footage::find($this->footageId);
        
        if (!$footage) {
            Log::error('Footage not found for embedding storage', ['footage_id' => $this->footageId]);
            return;
        }

        try {
            $storeEmbeddingAction->execute($footage);
        } catch (\Exception $e) {
            Log::error('Store embedding job failed', [
                'footage_id' => $this->footageId,
                'error' => $e->getMessage(),
            ]);

            // Don't fail the footage, embedding is optional
            // Just log the error
        }
    }
}
