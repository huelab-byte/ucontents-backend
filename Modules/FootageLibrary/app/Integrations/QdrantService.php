<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QdrantService
{
    private string $url;
    private ?string $apiKey;
    private string $collectionName;
    private int $vectorSize;

    public function __construct()
    {
        $config = config('footagelibrary.module.qdrant', [
            'url' => env('QDRANT_URL', 'http://localhost:6333'),
            'api_key' => env('QDRANT_API_KEY'),
            'collection_name' => env('QDRANT_FOOTAGE_COLLECTION', 'footage_embeddings'),
            'vector_size' => 1536,
        ]);
        $this->url = rtrim($config['url'], '/');
        $this->apiKey = $config['api_key'] ?? null;
        $this->collectionName = $config['collection_name'];
        $this->vectorSize = $config['vector_size'];
    }

    /**
     * Ensure collection exists, create if not
     */
    public function ensureCollection(): bool
    {
        if ($this->collectionExists()) {
            return true;
        }

        return $this->createCollection();
    }

    /**
     * Check if collection exists
     */
    public function collectionExists(): bool
    {
        try {
            $response = $this->makeRequest('GET', "/collections/{$this->collectionName}");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Qdrant collection check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Create collection
     */
    public function createCollection(): bool
    {
        try {
            $payload = [
                'vectors' => [
                    'size' => $this->vectorSize,
                    'distance' => 'Cosine',
                ],
            ];

            $response = $this->makeRequest('PUT', "/collections/{$this->collectionName}", $payload);
            
            if ($response->successful()) {
                Log::info('Qdrant collection created', ['collection' => $this->collectionName]);
                return true;
            }

            Log::error('Qdrant collection creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Qdrant collection creation error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Store a point (embedding) in Qdrant
     */
    public function storePoint(int|string $pointId, array $vector, array $payload): bool
    {
        try {
            // Validate vector before storing
            if (empty($vector)) {
                Log::error('Cannot store empty vector in Qdrant', ['point_id' => $pointId]);
                throw new \InvalidArgumentException('Vector cannot be empty');
            }

            $vectorSize = count($vector);
            if ($vectorSize !== $this->vectorSize) {
                Log::error('Vector size mismatch', [
                    'point_id' => $pointId,
                    'expected' => $this->vectorSize,
                    'actual' => $vectorSize,
                ]);
                throw new \InvalidArgumentException("Vector size mismatch: expected {$this->vectorSize}, got {$vectorSize}");
            }

            // Validate all vector values are numeric
            foreach ($vector as $i => $value) {
                if (!is_numeric($value)) {
                    Log::error('Vector contains non-numeric value', [
                        'point_id' => $pointId,
                        'index' => $i,
                        'value' => $value,
                    ]);
                    throw new \InvalidArgumentException("Vector contains non-numeric value at index {$i}");
                }
            }

            // Ensure point ID is numeric (Qdrant prefers unsigned 64-bit integers)
            $numericPointId = is_numeric($pointId) ? (int) $pointId : crc32($pointId);
            
            $data = [
                'points' => [
                    [
                        'id' => $numericPointId,
                        'vector' => array_map('floatval', $vector), // Ensure all values are floats
                        'payload' => $payload,
                    ],
                ],
            ];

            Log::debug('Storing point in Qdrant', [
                'collection' => $this->collectionName,
                'point_id' => $numericPointId,
                'vector_size' => $vectorSize,
            ]);

            $response = $this->makeRequest('PUT', "/collections/{$this->collectionName}/points", $data);
            
            if (!$response->successful()) {
                Log::error('Qdrant store point response error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Qdrant store point failed', [
                'point_id' => $pointId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to ensure caller knows about the failure
        }
    }

    /**
     * Search for similar vectors
     */
    public function search(array $vector, array $filters = [], int $limit = 10, float $scoreThreshold = 0.0): array
    {
        try {
            $query = [
                'vector' => $vector,
                'limit' => $limit,
                'score_threshold' => $scoreThreshold,
            ];

            if (!empty($filters)) {
                $query['filter'] = $this->buildFilter($filters);
            }

            $response = $this->makeRequest('POST', "/collections/{$this->collectionName}/points/search", $query);
            
            if ($response->successful()) {
                return $response->json('result', []);
            }

            Log::error('Qdrant search failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('Qdrant search error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Delete a point
     */
    public function deletePoint(int|string $pointId): bool
    {
        try {
            // Convert to numeric ID (our points use footage ID as numeric point ID)
            $numericPointId = is_numeric($pointId) ? (int) $pointId : crc32((string) $pointId);
            
            $response = $this->makeRequest('POST', "/collections/{$this->collectionName}/points/delete", [
                'points' => [$numericPointId],
            ]);
            
            Log::debug('Qdrant delete point request', [
                'point_id' => $numericPointId,
                'success' => $response->successful(),
            ]);
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Qdrant delete point failed', [
                'point_id' => $pointId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete collection and all its points
     */
    public function deleteCollection(): bool
    {
        try {
            $response = $this->makeRequest('DELETE', "/collections/{$this->collectionName}");
            
            if ($response->successful()) {
                Log::info('Qdrant collection deleted', ['collection' => $this->collectionName]);
                return true;
            }

            Log::error('Qdrant collection deletion failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Qdrant collection deletion error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get collection info
     */
    public function getCollectionInfo(): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/collections/{$this->collectionName}");
            
            if ($response->successful()) {
                return $response->json('result', []);
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Qdrant get collection info error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all point IDs in collection
     */
    public function getAllPointIds(int $limit = 10000): array
    {
        try {
            $response = $this->makeRequest('POST', "/collections/{$this->collectionName}/points/scroll", [
                'limit' => $limit,
                'with_payload' => false,
                'with_vector' => false,
            ]);
            
            if ($response->successful()) {
                $points = $response->json('result.points', []);
                return array_column($points, 'id');
            }
            return [];
        } catch (\Exception $e) {
            Log::error('Qdrant get all point IDs error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Delete multiple points by IDs
     */
    public function deletePoints(array $pointIds): bool
    {
        if (empty($pointIds)) {
            return true;
        }

        try {
            $response = $this->makeRequest('POST', "/collections/{$this->collectionName}/points/delete", [
                'points' => array_map('intval', $pointIds),
            ]);
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Qdrant delete points failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Build filter for Qdrant query
     */
    private function buildFilter(array $filters): array
    {
        $must = [];

        if (isset($filters['folder_id'])) {
            $must[] = [
                'key' => 'folder_id',
                'match' => ['value' => (int) $filters['folder_id']],
            ];
        }

        if (isset($filters['orientation'])) {
            $must[] = [
                'key' => 'orientation',
                'match' => ['value' => $filters['orientation']],
            ];
        }

        if (isset($filters['duration_min']) || isset($filters['duration_max'])) {
            $range = [];
            if (isset($filters['duration_min'])) {
                $range['gte'] = (float) $filters['duration_min'];
            }
            if (isset($filters['duration_max'])) {
                $range['lte'] = (float) $filters['duration_max'];
            }
            $must[] = [
                'key' => 'duration',
                'range' => $range,
            ];
        }

        return ['must' => $must];
    }

    /**
     * Make HTTP request to Qdrant API
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): \Illuminate\Http\Client\Response
    {
        $url = $this->url . $endpoint;
        $request = Http::timeout(30);

        if ($this->apiKey) {
            $request->withHeaders(['api-key' => $this->apiKey]);
        }

        return match ($method) {
            'GET' => $request->get($url),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }
}
