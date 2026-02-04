<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\AiIntegration\Services\AiModelCallService;
use Modules\AiIntegration\Services\AiApiKeyService;
use Modules\AiIntegration\DTOs\AiModelCallDTO;

/**
 * Generates a short, punchy in-video caption for burning onto the video.
 * Uses a dedicated prompt focused on word count (e.g. "Oh my gosh" = 3 words).
 */
class GenerateInVideoCaptionAction
{
    public function __construct(
        private AiModelCallService $aiService,
        private AiApiKeyService $apiKeyService
    ) {}

    /**
     * @param array $opts words_per_caption (int), context (string)
     * @return string The short in-video caption phrase
     */
    public function executeFromText(string $context, int $wordsPerCaption, ?int $userId = null): string
    {
        $cfg = config('mediaupload.module.content_generation', []);
        $provider = $cfg['ai_provider'] ?? 'openai';
        $model = $cfg['text_model'] ?? 'gpt-4o';
        $prompt = $this->buildTextPrompt($context, $wordsPerCaption);

        $apiKey = $this->apiKeyService->getBestApiKeyForScope($provider, 'text_caption');
        if (!$apiKey) {
            throw new \RuntimeException('No AI API key available for in-video caption generation');
        }

        $dto = new AiModelCallDTO(
            providerSlug: $provider,
            model: $model,
            prompt: $prompt,
            settings: ['temperature' => 0.5, 'max_tokens' => 100],
            module: 'MediaUpload',
            feature: 'in_video_caption',
            scope: 'text_caption',
        );
        $res = $this->aiService->callModel($dto, $userId);
        $text = trim($res['content'] ?? $res['message'] ?? '');
        return $this->cleanCaption($text, $wordsPerCaption);
    }

    /**
     * Generate from vision (merged frames) + title.
     */
    public function executeFromFrames(string $mergedFramePath, string $title, int $wordsPerCaption, ?int $userId = null): string
    {
        $cfg = config('mediaupload.module.content_generation', []);
        $provider = $cfg['ai_provider'] ?? 'openai';
        $model = $cfg['vision_model'] ?? 'gpt-4o';
        $prompt = $this->buildVisionPrompt($title, $wordsPerCaption);
        $image = base64_encode(file_get_contents($mergedFramePath));

        $apiKey = $this->apiKeyService->getBestApiKeyForScope($provider, 'vision_caption');
        if (!$apiKey) {
            throw new \RuntimeException('No AI API key available for in-video caption generation');
        }

        $dto = new AiModelCallDTO(
            providerSlug: $provider,
            model: $model,
            prompt: $prompt,
            settings: ['temperature' => 0.5, 'max_tokens' => 100],
            module: 'MediaUpload',
            feature: 'in_video_caption',
            metadata: ['image' => $image, 'image_format' => 'base64'],
            scope: 'vision_caption',
        );
        $res = $this->aiService->callModel($dto, $userId);
        $text = trim($res['content'] ?? $res['message'] ?? '');
        return $this->cleanCaption($text, $wordsPerCaption);
    }

    private function buildTextPrompt(string $context, int $wordsPerCaption): string
    {
        $n = max(1, min(10, $wordsPerCaption));
        $examples = match (true) {
            $n <= 2 => '"Oh wow" (2 words), "No way" (2 words)',
            $n <= 3 => '"Oh my gosh" (3 words), "That\'s insane" (3 words)',
            $n <= 4 => '"This is incredible" (4 words), "You won\'t believe this" (4 words)',
            $n <= 5 => '"This is so incredibly good" (5 words), "Watch this right now please" (5 words)',
            default => '"This is absolutely incredibly amazing right now" (6+ words)',
        };
        return <<<PROMPT
You generate SHORT in-video captions that get burned onto videos as text overlays. These are punchy, reaction-style phrases.

Context: {$context}

CRITICAL: You MUST generate exactly {$n} words — no more, no fewer. Count the words in your response.
Examples for {$n} words: {$examples}

Return ONLY the caption phrase. No quotes, no explanation, no punctuation at the end. Just exactly {$n} words.
PROMPT;
    }

    private function buildVisionPrompt(string $title, int $wordsPerCaption): string
    {
        $n = max(1, min(10, $wordsPerCaption));
        return <<<PROMPT
This image shows 6 frames from a video. Video title: "{$title}".

CRITICAL: Generate a SHORT in-video caption of exactly {$n} words. You MUST use exactly {$n} words — no more, no fewer. Count them.
Make it punchy and reaction-style (e.g. "Oh my gosh" = 3 words, "This is so incredibly good" = 5 words).

Return ONLY the caption phrase. No quotes, no explanation. Just exactly {$n} words.
PROMPT;
    }

    private function cleanCaption(string $text, int $wordsPerCaption): string
    {
        $text = preg_replace('/^["\']|["\']$/u', '', trim($text));
        $text = preg_replace('/\.+$/', '', $text);
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $n = max(1, min(10, $wordsPerCaption));
        if (count($words) > $n) {
            $words = array_slice($words, 0, $n);
        }
        return implode(' ', $words) ?: 'Watch this';
    }
}
