<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Modules\AiIntegration\Services\AiModelCallService;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Illuminate\Support\Facades\Log;

class GenerateMetadataFromTitleAction
{
    public function __construct(
        private AiModelCallService $aiService
    ) {}

    /**
     * Generate metadata from video title using AI
     */
    public function execute(string $title, ?int $userId = null): array
    {
        try {
            $config = config('footagelibrary.module.metadata', [
                'ai_provider' => env('FOOTAGE_METADATA_AI_PROVIDER', 'openai'),
                'ai_model' => env('FOOTAGE_METADATA_AI_MODEL', 'gpt-4'),
            ]);
            
            $prompt = $this->buildPrompt($title);
            
            $dto = new AiModelCallDTO(
                providerSlug: $config['ai_provider'],
                model: $config['ai_model'],
                prompt: $prompt,
                settings: [
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                ],
                module: 'FootageLibrary',
                feature: 'metadata_generation',
                scope: 'text_metadata',
            );

            $response = $this->aiService->callModel($dto, $userId);
            
            $content = $response['content'] ?? $response['message'] ?? '';
            
            // Parse JSON response
            $metadata = $this->parseMetadataResponse($content, $title);
            
            return $metadata;
        } catch (\Exception $e) {
            Log::error('Failed to generate metadata from title', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build prompt for AI
     */
    private function buildPrompt(string $title): string
    {
        return "Analyze this video title and generate structured metadata in JSON format. The video title is: \"{$title}\"

Return a JSON object with the following structure:
{
  \"description\": \"A detailed description of what the video likely contains\",
  \"tags\": [\"tag1\", \"tag2\", \"tag3\"],
  \"orientation\": \"horizontal\" or \"vertical\",
  \"estimated_duration\": 0.0,
  \"category\": \"category name\"
}

Focus on:
- What the video content likely shows based on the title
- Relevant tags that describe the content
- Whether it's likely horizontal or vertical (based on typical content)
- Estimated duration if the title suggests it (otherwise 0)
- A category that fits the content

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
            // Fallback: create basic metadata from title
            return [
                'description' => "Video: {$title}",
                'tags' => $this->extractTagsFromTitle($title),
                'orientation' => 'horizontal',
                'estimated_duration' => 0.0,
                'category' => 'general',
                'ai_metadata_source' => 'title',
            ];
        }

        return [
            'description' => $json['description'] ?? "Video: {$title}",
            'tags' => $json['tags'] ?? $this->extractTagsFromTitle($title),
            'orientation' => $json['orientation'] ?? 'horizontal',
            'estimated_duration' => (float) ($json['estimated_duration'] ?? 0.0),
            'category' => $json['category'] ?? 'general',
            'ai_metadata_source' => 'title',
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

    /**
     * Extract basic tags from title
     */
    private function extractTagsFromTitle(string $title): array
    {
        // Simple tag extraction - split by common separators
        $words = preg_split('/[\s\-_]+/', strtolower($title));
        return array_filter(array_unique($words), function ($word) {
            return strlen($word) > 2;
        });
    }
}
