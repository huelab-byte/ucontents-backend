<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Services;

use Modules\FootageLibrary\Integrations\QdrantService;
use Modules\AiIntegration\Services\AiModelCallService;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\FootageLibrary\Models\Footage;
use Illuminate\Support\Facades\Log;

class VectorSearchService
{
    public function __construct(
        private QdrantService $qdrantService,
        private AiModelCallService $aiService
    ) {}

    /**
     * Search for footage using vector similarity
     */
    public function search(
        string $searchText,
        array $filters = [],
        int $limit = 10,
        float $minSimilarity = 0.5
    ): array {
        try {
            // Generate embedding for search text
            $embedding = $this->generateEmbedding($searchText);

            // Search in Qdrant
            $results = $this->qdrantService->search($embedding, $filters, $limit * 2, $minSimilarity);

            // Apply diversity algorithm
            $diverseResults = $this->applyDiversityAlgorithm($results, $limit);

            // Load footage models
            $footageIds = array_column($diverseResults, 'footage_id');
            $footage = Footage::whereIn('id', $footageIds)
                ->with(['storageFile', 'folder'])
                ->get()
                ->keyBy('id');

            // Map results with footage data
            $mappedResults = [];
            foreach ($diverseResults as $result) {
                $footageId = $result['payload']['footage_id'];
                if (isset($footage[$footageId])) {
                    $mappedResults[] = [
                        'footage' => $footage[$footageId],
                        'score' => $result['score'] ?? 0.0,
                    ];
                }
            }

            return $mappedResults;
        } catch (\Exception $e) {
            Log::error('Vector search failed', [
                'search_text' => $searchText,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Apply diversity algorithm to avoid repetitive footage
     */
    private function applyDiversityAlgorithm(array $results, int $limit, float $diversityThreshold = 0.7): array
    {
        if (count($results) <= $limit) {
            return $results;
        }

        $selected = [];
        $usedEmbeddings = [];

        foreach ($results as $result) {
            if (count($selected) >= $limit) {
                break;
            }

            $embedding = $result['vector'] ?? null;
            if (!$embedding) {
                continue;
            }

            // Check diversity against already selected items
            $isDiverse = true;
            foreach ($usedEmbeddings as $usedEmbedding) {
                $similarity = $this->cosineSimilarity($embedding, $usedEmbedding);
                if ($similarity > $diversityThreshold) {
                    $isDiverse = false;
                    break;
                }
            }

            if ($isDiverse) {
                $selected[] = $result;
                $usedEmbeddings[] = $embedding;
            }
        }

        // If we don't have enough diverse results, fill with remaining high-scoring ones
        if (count($selected) < $limit) {
            foreach ($results as $result) {
                if (count($selected) >= $limit) {
                    break;
                }
                
                $alreadySelected = false;
                foreach ($selected as $sel) {
                    if ($sel['payload']['footage_id'] === $result['payload']['footage_id']) {
                        $alreadySelected = true;
                        break;
                    }
                }
                
                if (!$alreadySelected) {
                    $selected[] = $result;
                }
            }
        }

        return $selected;
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Generate embedding for search text
     */
    private function generateEmbedding(string $text): array
    {
        $config = config('footagelibrary.module.metadata', [
            'ai_provider' => env('FOOTAGE_METADATA_AI_PROVIDER', 'openai'),
            'embedding_model' => env('FOOTAGE_EMBEDDING_MODEL', 'text-embedding-ada-002'),
        ]);
        
        $dto = new AiModelCallDTO(
            providerSlug: $config['ai_provider'],
            model: $config['embedding_model'],
            prompt: $text,
            module: 'FootageLibrary',
            feature: 'search_embedding',
        );

        $response = $this->aiService->callModel($dto);
        
        // Extract embedding from response
        if (isset($response['embedding'])) {
            return $response['embedding'];
        }
        
        if (isset($response['data'][0]['embedding'])) {
            return $response['data'][0]['embedding'];
        }

        throw new \RuntimeException('Could not extract embedding from AI response');
    }
}
