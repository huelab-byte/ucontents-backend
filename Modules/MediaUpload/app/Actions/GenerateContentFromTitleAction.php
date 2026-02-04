<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\AiIntegration\Services\AiModelCallService;
use Modules\AiIntegration\Services\AiApiKeyService;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Illuminate\Support\Facades\Log;

class GenerateContentFromTitleAction
{
    public function __construct(
        private AiModelCallService $aiService,
        private AiApiKeyService $apiKeyService
    ) {}

    /**
     * @param array $opts heading_length, heading_emoji, caption_length, hashtag_count
     * @return array{youtube_heading: string, social_caption: string, hashtags: string[]}
     */
    public function execute(string $title, array $opts, ?int $userId = null): array
    {
        $cfg = config('mediaupload.module.content_generation', []);
        $provider = $cfg['ai_provider'] ?? 'openai';
        $model = $cfg['text_model'] ?? 'gpt-4o';
        $prompt = $this->buildPrompt($title, $opts);

        $apiKey = $this->apiKeyService->getBestApiKey($provider);
        if (!$apiKey) {
            throw new \RuntimeException('No AI API key available for content generation');
        }

        $dto = new AiModelCallDTO(
            providerSlug: $provider,
            model: $model,
            prompt: $prompt,
            settings: ['temperature' => 0.7, 'max_tokens' => 1500],
            module: 'MediaUpload',
            feature: 'content_generation',
            scope: 'text_content',
        );
        $res = $this->aiService->callModel($dto, $userId);
        $text = $res['content'] ?? $res['message'] ?? '';
        return $this->parseResponse($text, $title, $opts);
    }

    private function buildPrompt(string $title, array $opts): string
    {
        $headingWords = max(1, (int) ($opts['heading_length'] ?? 10));
        $emoji = !empty($opts['heading_emoji']);
        $captionWords = max(1, (int) ($opts['caption_length'] ?? 30));
        $hc = max(1, (int) ($opts['hashtag_count'] ?? 3));

        $emojiLine = $emoji
            ? 'MANDATORY: Include 1–2 relevant emojis in the heading.'
            : 'Do not use emojis in the heading.';
        
        return <<<PROMPT
You are a social media content expert. Based on this video title, create engaging content.

Video title: "{$title}"

CRITICAL LENGTH REQUIREMENTS - YOU MUST FOLLOW THESE EXACTLY:
1. YouTube Heading: EXACTLY {$headingWords} words (not more, not less). {$emojiLine}
2. Social Caption: EXACTLY {$captionWords} words (not more, not less). Include a hook and call-to-action.
3. Hashtags: EXACTLY {$hc} hashtags with # symbol.

Create ORIGINAL content that expands on the title theme. Do NOT just repeat the title.

Return ONLY a valid JSON object:
{"youtube_heading": "...", "social_caption": "...", "hashtags": ["#tag1", "#tag2", ...]}
PROMPT;
    }

    private function parseResponse(string $text, string $title, array $opts): array
    {
        $json = $this->extractJson($text);
        $hc = (int) ($opts['hashtag_count'] ?? 3);
        if ($json) {
            $tags = $json['hashtags'] ?? [];
            if (!is_array($tags)) {
                $tags = array_slice(array_filter(array_map('trim', explode(',', is_string($tags) ? $tags : ''))), 0, $hc);
            }
            $tags = array_slice(array_map(function ($t) {
                $t = trim((string) $t);
                return $t !== '' && ($t[0] ?? '') !== '#' ? '#' . $t : $t;
            }, $tags), 0, $hc);
            $heading = $json['youtube_heading'] ?? 'Video: ' . $title;
            $heading = $this->ensureHeadingEmoji($heading, $opts);
            return [
                'youtube_heading' => $heading,
                'social_caption' => $json['social_caption'] ?? '',
                'hashtags' => $tags,
            ];
        }
        $heading = $this->ensureHeadingEmoji('Video: ' . $title, $opts);
        return [
            'youtube_heading' => $heading,
            'social_caption' => '',
            'hashtags' => [],
        ];
    }

    private function ensureHeadingEmoji(string $heading, array $opts): string
    {
        if (empty($opts['heading_emoji'])) {
            return $heading;
        }
        if (preg_match('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F600}-\x{1F64F}\x{1F1E0}-\x{1F1FF}]/u', $heading)) {
            return $heading;
        }
        return '🎬 ' . $heading;
    }

    private function extractJson(string $text): ?array
    {
        // Remove markdown code blocks wrapper if they exist
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $text, $matches)) {
            $text = $matches[1];
        }

        // Try to decode the whole text first (fast path)
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Scan for JSON objects using brace balancing
        $candidates = [];
        $balance = 0;
        $start = -1;
        $len = strlen($text);
        $inString = false;
        $escape = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                if ($balance === 0) {
                    $start = $i;
                }
                $balance++;
            } elseif ($char === '}') {
                if ($balance > 0) {
                    $balance--;
                    if ($balance === 0 && $start !== -1) {
                        $jsonStr = substr($text, $start, $i - $start + 1);
                        $decoded = json_decode($jsonStr, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $candidates[] = $decoded;
                        }
                        // We reset start to look for the next object
                        $start = -1; 
                    }
                }
            }
        }

        // Return the last valid candidate that has at least one expected key
        // Iterating backwards to prioritize the final response over any examples in the prompt
        for ($i = count($candidates) - 1; $i >= 0; $i--) {
            $c = $candidates[$i];
            if (isset($c['youtube_heading']) || isset($c['social_caption']) || isset($c['hashtags'])) {
                return $c;
            }
        }

        // Fallback: Return the last candidate found found (best effort)
        if (!empty($candidates)) {
            return end($candidates);
        }

        return null;
    }
}
