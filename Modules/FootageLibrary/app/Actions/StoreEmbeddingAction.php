<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Integrations\QdrantService;
use Modules\AiIntegration\Services\AiModelCallService;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StoreEmbeddingAction
{
    public function __construct(
        private QdrantService $qdrantService,
        private AiModelCallService $aiService
    ) {}

    /**
     * Generate and store embedding for footage
     */
    public function execute(Footage $footage): void
    {
        try {
            // Ensure collection exists
            $this->qdrantService->ensureCollection();

            // Generate embedding text
            $embeddingText = $this->buildEmbeddingText($footage);

            // Generate embedding using OpenAI
            $embedding = $this->generateEmbedding($embeddingText, $footage->user_id);

            // Use footage ID as point ID (Qdrant supports numeric IDs)
            $pointId = $footage->id;

            // Prepare payload for Qdrant
            $payload = [
                'footage_id' => $footage->id,
                'folder_id' => $footage->folder_id,
                'orientation' => $footage->metadata['orientation'] ?? 'horizontal',
                'duration' => (float) ($footage->metadata['duration'] ?? 0.0),
                'title' => $footage->title,
                'description' => $footage->metadata['description'] ?? '',
            ];

            Log::info('Storing embedding in Qdrant', [
                'footage_id' => $footage->id,
                'point_id' => $pointId,
                'embedding_length' => count($embedding),
            ]);

            // Store in Qdrant
            $success = $this->qdrantService->storePoint($pointId, $embedding, $payload);

            if ($success) {
                $footage->update(['embedding_id' => (string) $pointId]);
                Log::info('Embedding stored successfully', ['footage_id' => $footage->id]);
            } else {
                throw new \RuntimeException('Failed to store embedding in Qdrant');
            }
        } catch (\Exception $e) {
            Log::error('Failed to store embedding', [
                'footage_id' => $footage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build text for embedding generation
     */
    private function buildEmbeddingText(Footage $footage): string
    {
        $parts = [$footage->title];
        
        if (!empty($footage->metadata['description'])) {
            $parts[] = $footage->metadata['description'];
        }
        
        if (!empty($footage->metadata['tags']) && is_array($footage->metadata['tags'])) {
            $parts[] = implode(' ', $footage->metadata['tags']);
        }
        
        if (!empty($footage->metadata['scene_description'])) {
            $parts[] = $footage->metadata['scene_description'];
        }
        
        if (!empty($footage->metadata['category'])) {
            $parts[] = $footage->metadata['category'];
        }

        return implode(' ', $parts);
    }

    /**
     * Generate embedding using OpenAI
     */
    private function generateEmbedding(string $text, ?int $userId): array
    {
        $config = config('footagelibrary.module.metadata', [
            'ai_provider' => env('FOOTAGE_METADATA_AI_PROVIDER', 'openai'),
            'embedding_model' => env('FOOTAGE_EMBEDDING_MODEL', 'text-embedding-ada-002'),
        ]);
        
        $expectedSize = config('footagelibrary.module.qdrant.vector_size', 1536);
        
        // Use OpenAI embeddings API
        // Note: This requires the AiIntegration module to support embeddings
        // For now, we'll use a workaround with the model call service
        // In production, you'd want a dedicated embeddings endpoint
        
        $dto = new AiModelCallDTO(
            providerSlug: $config['ai_provider'],
            model: $config['embedding_model'],
            prompt: $text,
            module: 'FootageLibrary',
            feature: 'embedding_generation',
            scope: 'embedding',
        );

        $response = $this->aiService->callModel($dto, $userId);
        
        // Extract embedding from response
        // This depends on how the AI adapter returns embeddings
        $embedding = null;
        
        if (isset($response['embedding']) && is_array($response['embedding'])) {
            $embedding = $response['embedding'];
        } elseif (isset($response['data'][0]['embedding']) && is_array($response['data'][0]['embedding'])) {
            $embedding = $response['data'][0]['embedding'];
        }

        // Validate embedding
        if ($embedding === null || empty($embedding)) {
            Log::error('Empty embedding received from AI', [
                'response_keys' => array_keys($response ?? []),
            ]);
            throw new \RuntimeException('Empty embedding received from AI response');
        }

        $actualSize = count($embedding);
        if ($actualSize !== $expectedSize) {
            Log::error('Embedding size mismatch', [
                'expected' => $expectedSize,
                'actual' => $actualSize,
            ]);
            throw new \RuntimeException("Embedding size mismatch: expected {$expectedSize}, got {$actualSize}");
        }

        return $embedding;
    }
}
