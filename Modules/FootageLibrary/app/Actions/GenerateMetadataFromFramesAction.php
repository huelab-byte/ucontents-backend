<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Modules\AiIntegration\Services\AiModelCallService;
use Modules\AiIntegration\Services\AiApiKeyService;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateMetadataFromFramesAction
{
    public function __construct(
        private AiModelCallService $aiService,
        private AiApiKeyService $apiKeyService
    ) {}

    /**
     * Generate metadata from video frames using AI vision model
     * Tries multiple providers/models if configured
     */
    public function execute(string $mergedFramePath, string $title, ?int $userId = null): array
    {
        $config = config('footagelibrary.module.metadata', []);
        
        // Read image and convert to base64
        $imageData = file_get_contents($mergedFramePath);
        $base64Image = base64_encode($imageData);
        
        $prompt = $this->buildPrompt($title);
        
        // Build list of providers/models to try
        $attempts = $this->buildAttemptList($config);
        
        $lastError = null;
        
        foreach ($attempts as $attempt) {
            try {
                // Check if this provider has an available API key
                $apiKey = $this->apiKeyService->getBestApiKey($attempt['provider']);
                if (!$apiKey) {
                    Log::debug('No API key available for provider', ['provider' => $attempt['provider']]);
                    continue;
                }
                
                Log::info('Attempting AI vision call', [
                    'provider' => $attempt['provider'],
                    'model' => $attempt['model'],
                    'title' => $title,
                ]);
                
                $dto = new AiModelCallDTO(
                    providerSlug: $attempt['provider'],
                    model: $attempt['model'],
                    prompt: $prompt,
                    settings: [
                        'temperature' => 0.7,
                        'max_tokens' => 1000,
                    ],
                    module: 'FootageLibrary',
                    feature: 'metadata_generation',
                    metadata: [
                        'image' => $base64Image,
                        'image_format' => 'base64',
                    ],
                );

                $response = $this->aiService->callModel($dto, $userId);
                
                $content = $response['content'] ?? $response['message'] ?? '';
                
                // Parse JSON response
                $metadata = $this->parseMetadataResponse($content, $title);
                $metadata['ai_provider'] = $attempt['provider'];
                $metadata['ai_model'] = $attempt['model'];
                
                Log::info('AI vision call successful', [
                    'provider' => $attempt['provider'],
                    'model' => $attempt['model'],
                ]);
                
                return $metadata;
                
            } catch (\Exception $e) {
                $lastError = $e;
                Log::warning('AI vision call failed, trying next provider', [
                    'provider' => $attempt['provider'],
                    'model' => $attempt['model'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // All attempts failed
        Log::error('Failed to generate metadata from frames - all providers failed', [
            'title' => $title,
            'last_error' => $lastError?->getMessage(),
        ]);
        
        throw $lastError ?? new \Exception('No AI providers available for vision');
    }
    
    /**
     * Build list of provider/model combinations to try
     */
    private function buildAttemptList(array $config): array
    {
        $attempts = [];
        
        // Add primary provider/model
        $primaryProvider = $config['ai_provider'] ?? 'openai';
        $primaryModel = $config['vision_model'] ?? 'gpt-4o';
        $attempts[] = ['provider' => $primaryProvider, 'model' => $primaryModel];
        
        // Add fallbacks
        $fallbacks = $config['vision_fallbacks'] ?? [];
        foreach ($fallbacks as $fallback) {
            // Avoid duplicates
            $key = $fallback['provider'] . ':' . $fallback['model'];
            $primaryKey = $primaryProvider . ':' . $primaryModel;
            if ($key !== $primaryKey) {
                $attempts[] = $fallback;
            }
        }
        
        return $attempts;
    }

    /**
     * Build prompt for AI vision model
     */
    private function buildPrompt(string $title): string
    {
        return "Analyze these video frames (merged into one image) and the video title to generate structured metadata in JSON format.

Video title: \"{$title}\"

The image shows 6 frames extracted from the video at different time points.

Return a JSON object with the following structure:
{
  \"description\": \"A detailed description of what the video contains based on the frames\",
  \"tags\": [\"tag1\", \"tag2\", \"tag3\"],
  \"orientation\": \"horizontal\" or \"vertical\" (based on frame dimensions),
  \"estimated_duration\": 0.0,
  \"category\": \"category name\",
  \"scene_description\": \"What is happening in the video\",
  \"mood\": \"mood or atmosphere\"
}

Focus on:
- What the video actually shows based on the frames
- Visual elements, actions, objects, people, settings
- The orientation (horizontal/vertical) based on frame aspect ratio
- Relevant tags describing the visual content
- Scene description and mood

Return ONLY valid JSON, no additional text.";
    }

    /**
     * Parse AI response and extract metadata
     */
    private function parseMetadataResponse(string $content, string $title): array
    {
        // Try to extract JSON from response
        $json = $this->extractJson($content);
        
        if (!$json) {
            // Fallback: create basic metadata
            return [
                'description' => "Video: {$title}",
                'tags' => [],
                'orientation' => 'horizontal',
                'estimated_duration' => 0.0,
                'category' => 'general',
                'scene_description' => '',
                'mood' => '',
                'ai_metadata_source' => 'frames',
            ];
        }

        return [
            'description' => $json['description'] ?? "Video: {$title}",
            'tags' => $json['tags'] ?? [],
            'orientation' => $json['orientation'] ?? 'horizontal',
            'estimated_duration' => (float) ($json['estimated_duration'] ?? 0.0),
            'category' => $json['category'] ?? 'general',
            'scene_description' => $json['scene_description'] ?? '',
            'mood' => $json['mood'] ?? '',
            'ai_metadata_source' => 'frames',
        ];
    }

    /**
     * Extract JSON from AI response
     */
    private function extractJson(string $content): ?array
    {
        // Try to find JSON object
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        return null;
    }
}
