<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Modules\FootageLibrary\Services\VectorSearchService;
use Modules\FootageLibrary\DTOs\SearchFootageDTO;
use Illuminate\Support\Facades\Log;

class SearchFootageAction
{
    public function __construct(
        private VectorSearchService $vectorSearchService
    ) {}

    /**
     * Search for footage matching the content
     */
    public function execute(SearchFootageDTO $dto): array
    {
        try {
            // Build filters
            $filters = [];
            
            if ($dto->folderId) {
                $filters['folder_id'] = $dto->folderId;
            }
            
            if ($dto->orientation) {
                $filters['orientation'] = $dto->orientation;
            }
            
            if ($dto->footageLength) {
                // Allow some tolerance (±0.5 seconds)
                $filters['duration_min'] = $dto->footageLength - 0.5;
                $filters['duration_max'] = $dto->footageLength + 0.5;
            }

            // Calculate required footage count
            $requiredCount = $this->calculateRequiredCount($dto->contentLength, $dto->footageLength);
            
            // Search with higher limit to ensure diversity
            $limit = min($requiredCount * 2, config('footagelibrary.module.search.max_results', 1000));
            
            // Perform vector search (pass userId so customer's API key is used when configured)
            $results = $this->vectorSearchService->search(
                $dto->searchText,
                $filters,
                $limit,
                config('footagelibrary.module.search.min_similarity', 0.5),
                $dto->userId
            );

            // Limit to required count
            $results = array_slice($results, 0, $requiredCount);

            return $results;
        } catch (\Exception $e) {
            Log::error('Footage search failed', [
                'search_text' => $dto->searchText,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate required footage count based on content length and footage length
     */
    private function calculateRequiredCount(float $contentLengthSeconds, float $footageLengthSeconds): int
    {
        if ($footageLengthSeconds <= 0) {
            return 10; // Default
        }

        return (int) ceil($contentLengthSeconds / $footageLengthSeconds);
    }
}
