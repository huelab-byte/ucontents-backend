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
        'qwen2-vl-7b',
        'moondream2',
        'moondream',
    ];

    /**
     * Text generation models
     */
    private const TEXT_MODELS = [
        'qwen2-vl-7b',
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

            Log::debug('Ucontents raw text response', ['response' => $rawContent]);

            // Clean the response - remove the prompt from the beginning
            $content = $this->cleanResponse($rawContent, $dto->prompt);

            Log::debug('Ucontents cleaned text response', ['content' => $content]);

            // Estimate token count (approximate: 4 chars per token)
            $promptTokens = (int) ceil(strlen($dto->prompt) / 4);
            $completionTokens = (int) ceil(strlen($content) / 4);

            return [
                'content' => $content,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
                'model' => $dto->model ?: 'qwen2-vl-7b',
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
            $request = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->timeout(180);

            // Handle image based on format
            if ($imageFormat === 'base64') {
                $imageData = base64_decode($image);
                $request->attach('file', $imageData, 'image.jpg', ['Content-Type' => 'image/jpeg']);
            } elseif ($imageFormat === 'url') {
                $imageResponse = Http::get($image);
                if ($imageResponse->successful()) {
                    $request->attach('file', $imageResponse->body(), 'image.jpg', ['Content-Type' => 'image/jpeg']);
                } else {
                    throw new \Exception('Failed to download image from URL');
                }
            } elseif ($imageFormat === 'path') {
                if (!file_exists($image)) {
                    throw new \Exception("Image file not found: {$image}");
                }
                $request->attach('file', fopen($image, 'r'), basename($image));
            }

            $response = $request->post("{$baseUrl}/analyze", [
                'prompt' => $dto->prompt,
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                throw new \Exception("Ucontents Vision API error: {$response->status()} - {$errorBody}");
            }

            $data = $response->json();
            $description = $data['response'] ?? '';

            Log::debug('Ucontents Vision analysis (step 1)', ['description' => $description]);

            if (empty($description)) {
                throw new \Exception('Vision model returned empty description');
            }

            // Step 2: Use the description to generate the final content via text model
            // We prepend the description to the original prompt.
            $originalPrompt = $dto->prompt;
            $augmentedPrompt = "### CONTEXT: VIDEO FRAME ANALYSIS\n" .
                "Below is a descriptive summary of what is happening in the video frames:\n\n" .
                "VISUAL SUMMARY: " . $description . "\n\n" .
                "### TASK:\n" .
                "Using ONLY the 'VISUAL SUMMARY' above as your source of truth, fulfill the following request. " .
                "DO NOT mention that this is a 'collage', 'grid', 'four frames', or 'screenshots'. Talk about it as a single cohesive video scene.\n\n" .
                "### REQUEST:\n" .
                $originalPrompt;

            Log::debug('Ucontents Vision augmentation (step 2)', ['prompt' => substr($augmentedPrompt, 0, 500) . '...']);

            // Create a new DTO for the text model call
            $textDto = new AiModelCallDTO(
                providerSlug: $dto->providerSlug,
                model: 'qwen2-vl-7b', // Fallback to text model
                prompt: $augmentedPrompt,
                settings: $dto->settings,
                module: $dto->module,
                feature: $dto->feature,
                metadata: [], // No image for step 2
                scope: 'text_content',
            );

            $textResult = $this->callTextModel($baseUrl, $apiKey, $textDto);

            // Estimate token count for the vision part
            $promptTokens = (int) ceil(strlen($dto->prompt) / 4);
            $completionTokens = (int) ceil(strlen($description) / 4);

            return [
                'content' => $textResult['content'],
                'prompt_tokens' => $promptTokens + ($textResult['prompt_tokens'] ?? 0),
                'completion_tokens' => $completionTokens + ($textResult['completion_tokens'] ?? 0),
                'total_tokens' => ($promptTokens + $completionTokens) + ($textResult['total_tokens'] ?? 0),
                'model' => $dto->model ?: 'qwen2-vl-7b',
                'description' => $description, // Pass through description for reference if needed
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
            $response = trim(substr($response, strlen($prompt)));
        }

        // Also check for [INST] format that Mistral uses: "[INST] prompt [/INST] response"
        // The API might return: "[INST] prompt [/INST] actual_response"
        if (preg_match('/\[\/INST\]\s*(.*)$/s', $response, $matches)) {
            $response = trim($matches[1]);
        }

        // If the response is already short and doesn't seem to contain the prompt,
        // we should be careful not to over-clean it.
        // Qwen2-VL already trims the input, so if it's clean, we're good.

        // Final sanity check: if it still starts with exactly what we asked but in a different case
        $lowerResponse = strtolower($response);
        $lowerPrompt = strtolower($prompt);

        if (str_starts_with($lowerResponse, $lowerPrompt) && strlen($response) > strlen($prompt)) {
            $response = trim(substr($response, strlen($prompt)));
        }

        // Remove common prefixes that might remain after stripping prompt
        // but ONLY if they are very short and at the very beginning.
        $prefixesToRemove = [
            ':',
            '-',
            '–',
            '→',
        ];

        while (!empty($response) && in_array(substr($response, 0, 1), $prefixesToRemove)) {
            $response = trim(substr($response, 1));
        }

        return $response;
    }
}
