<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Adapters;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\AiIntegration\Models\AiApiKey;

/**
 * Ucontents Hosted AI Adapter
 * 
 * Custom adapter for Ucontents self-hosted AI service at https://gpt.ucontents.com
 * 
 * Endpoints:
 * - GET /generate - Text generation (Mistral)
 * - POST /analyze - Image analysis (Moondream)
 */
class UcontentsAdapter implements ProviderAdapterInterface
{
    /**
     * Default base URL for Ucontents API
     */
    private const DEFAULT_BASE_URL = 'https://gpt.ucontents.com';

    /**
     * Models that support vision/image analysis
     */
    private const VISION_MODELS = [
        'moondream2',
        'moondream',
    ];

    /**
     * Text generation models
     */
    private const TEXT_MODELS = [
        'mistral-7b-instruct',
        'mistral',
    ];

    public function callModel(AiApiKey $apiKey, AiModelCallDTO $dto): array
    {
        $baseUrl = $apiKey->endpoint_url ?? $apiKey->provider->base_url ?? self::DEFAULT_BASE_URL;
        $apiKeyValue = $apiKey->getDecryptedApiKey();

        // Determine if this is a vision request
        $hasImage = !empty($dto->metadata['image']);
        $isVisionModel = $this->isVisionModel($dto->model);

        if ($hasImage && $isVisionModel) {
            return $this->callVisionModel($baseUrl, $apiKeyValue, $dto);
        }

        return $this->callTextModel($baseUrl, $apiKeyValue, $dto);
    }

    /**
     * Check if the model is a vision model
     */
    private function isVisionModel(string $model): bool
    {
        foreach (self::VISION_MODELS as $visionModel) {
            if (stripos($model, $visionModel) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Call the text generation endpoint (GET /generate)
     */
    private function callTextModel(string $baseUrl, string $apiKey, AiModelCallDTO $dto): array
    {
        $settings = array_merge([
            'max_tokens' => 500,
        ], $dto->settings ?? []);

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(120)
            ->get("{$baseUrl}/generate", [
                'prompt' => $dto->prompt,
                'max_tokens' => $settings['max_tokens'],
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                throw new \Exception("Ucontents API error: {$response->status()} - {$errorBody}");
            }

            $data = $response->json();
            $rawContent = $data['response'] ?? '';

            // Clean the response - remove the prompt from the beginning
            $content = $this->cleanResponse($rawContent, $dto->prompt);

            // Estimate token count (approximate: 4 chars per token)
            $promptTokens = (int) ceil(strlen($dto->prompt) / 4);
            $completionTokens = (int) ceil(strlen($content) / 4);

            return [
                'content' => $content,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
                'model' => $dto->model ?: 'mistral-7b-instruct',
            ];

        } catch (\Exception $e) {
            Log::error('Ucontents text API call failed', [
                'error' => $e->getMessage(),
                'prompt' => substr($dto->prompt, 0, 100),
            ]);
            throw $e;
        }
    }

    /**
     * Call the vision/image analysis endpoint (POST /analyze)
     */
    private function callVisionModel(string $baseUrl, string $apiKey, AiModelCallDTO $dto): array
    {
        $image = $dto->metadata['image'] ?? null;
        $imageFormat = $dto->metadata['image_format'] ?? 'base64';

        if (!$image) {
            throw new \Exception('Image is required for vision model');
        }

        try {
            // Prepare the multipart request
            $multipart = [
                [
                    'name' => 'prompt',
                    'contents' => $dto->prompt,
                ],
            ];

            // Handle image based on format
            if ($imageFormat === 'base64') {
                // Convert base64 to binary for upload
                $imageData = base64_decode($image);
                $multipart[] = [
                    'name' => 'file',
                    'contents' => $imageData,
                    'filename' => 'image.jpg',
                    'headers' => ['Content-Type' => 'image/jpeg'],
                ];
            } elseif ($imageFormat === 'url') {
                // Download the image first
                $imageResponse = Http::get($image);
                if ($imageResponse->successful()) {
                    $multipart[] = [
                        'name' => 'file',
                        'contents' => $imageResponse->body(),
                        'filename' => 'image.jpg',
                        'headers' => ['Content-Type' => 'image/jpeg'],
                    ];
                } else {
                    throw new \Exception('Failed to download image from URL');
                }
            } elseif ($imageFormat === 'path') {
                // Read from file path
                if (!file_exists($image)) {
                    throw new \Exception("Image file not found: {$image}");
                }
                $multipart[] = [
                    'name' => 'file',
                    'contents' => fopen($image, 'r'),
                    'filename' => basename($image),
                ];
            }

            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])
            ->timeout(180)
            ->asMultipart()
            ->post("{$baseUrl}/analyze", $multipart);

            if (!$response->successful()) {
                $errorBody = $response->body();
                throw new \Exception("Ucontents Vision API error: {$response->status()} - {$errorBody}");
            }

            $data = $response->json();
            $rawContent = $data['response'] ?? '';

            // Clean the response - remove the prompt from the beginning
            $content = $this->cleanResponse($rawContent, $dto->prompt);

            // Estimate token count
            $promptTokens = (int) ceil(strlen($dto->prompt) / 4);
            $completionTokens = (int) ceil(strlen($content) / 4);

            return [
                'content' => $content,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
                'model' => $dto->model ?: 'moondream2',
            ];

        } catch (\Exception $e) {
            Log::error('Ucontents Vision API call failed', [
                'error' => $e->getMessage(),
                'prompt' => substr($dto->prompt, 0, 100),
            ]);
            throw $e;
        }
    }

    /**
     * Clean the response by removing the prompt from the beginning.
     * 
     * The Ucontents API returns the prompt at the beginning of the response,
     * which needs to be stripped for a clean response.
     */
    private function cleanResponse(string $response, string $prompt): string
    {
        // Trim whitespace
        $response = trim($response);
        $prompt = trim($prompt);

        if (empty($response)) {
            return '';
        }

        // Check if response starts with the exact prompt
        if (str_starts_with($response, $prompt)) {
            $response = substr($response, strlen($prompt));
        }

        // Also check for [INST] format that Mistral uses: "[INST] prompt [/INST] response"
        // The API might return: "[INST] prompt [/INST] actual_response"
        if (preg_match('/\[\/INST\]\s*(.*)$/s', $response, $matches)) {
            $response = $matches[1];
        }

        // Remove leading/trailing whitespace after cleanup
        $response = trim($response);

        // Remove any remaining leading prompt artifacts
        // Sometimes there might be variations with extra spaces or cases
        $lowerResponse = strtolower($response);
        $lowerPrompt = strtolower($prompt);
        
        if (str_starts_with($lowerResponse, $lowerPrompt)) {
            $response = substr($response, strlen($prompt));
            $response = trim($response);
        }

        // Remove common prefixes that might remain
        $prefixesToRemove = [
            ':',
            '-',
            '–',
            '→',
            '\n',
        ];

        while (!empty($response) && in_array(substr($response, 0, 1), $prefixesToRemove)) {
            $response = trim(substr($response, 1));
        }

        return $response;
    }
}
