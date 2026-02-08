<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\AiIntegration\Services\AiModelCallService;
use Modules\AiIntegration\Services\AiApiKeyService;
use Modules\AiIntegration\DTOs\AiModelCallDTO;

class GenerateContentFromFramesAction
{
    public function __construct(
        private AiModelCallService $aiService,
        private AiApiKeyService $apiKeyService
    ) {
    }

    public function execute(string $mergedFramePath, string $title, array $opts, ?int $userId = null, string $customPrompt = ''): array
    {
        $cfg = config('mediaupload.module.content_generation', []);
        // Use only fallback list: first provider with an active key (in AI Integration) wins. No separate setting.
        $attempts = $cfg['vision_fallbacks'] ?? [];
        if (empty($attempts)) {
            throw new \RuntimeException(
                'No AI providers configured for vision content. Add vision_fallbacks in config/mediaupload.module.content_generation and add active API keys in AI Integration.'
            );
        }

        $prompt = $this->buildPrompt($title, $opts, $customPrompt);
        $image = base64_encode(file_get_contents($mergedFramePath));
        $lastException = null;

        foreach ($attempts as $attempt) {
            try {
                $providerSlug = $attempt['provider'];
                $model = $attempt['model'];

                $apiKey = $this->apiKeyService->getBestApiKeyForScope($providerSlug, 'vision_content', null, $userId);

                if (!$apiKey) {
                    continue; // No key for this provider, try next
                }

                $dto = new AiModelCallDTO(
                    providerSlug: $providerSlug,
                    model: $model,
                    prompt: $prompt,
                    settings: ['temperature' => 0.7, 'max_tokens' => 1500],
                    module: 'MediaUpload',
                    feature: 'content_generation',
                    metadata: ['image' => $image, 'image_format' => 'base64'],
                    scope: 'vision_content',
                );

                $res = $this->aiService->callModel($dto, $userId);
                $text = $res['content'] ?? $res['message'] ?? '';

                return $this->parseResponse($text, $title, $opts);

            } catch (\Exception $e) {
                $lastException = $e;
                \Log::warning("AI content generation failed for provider {$attempt['provider']}: " . $e->getMessage());
                continue; // Try next fallback
            }
        }

        // If we get here, all attempts failed
        throw new \RuntimeException(
            'Failed to generate content. All AI providers failed. Last error: ' .
            ($lastException ? $lastException->getMessage() : 'No active API keys found.')
        );
    }

    private function buildPrompt(string $title, array $opts, string $customPrompt = ''): string
    {
        $headingWords = max(1, (int) ($opts['heading_length'] ?? 10));
        $emoji = !empty($opts['heading_emoji']);
        $captionWords = max(1, (int) ($opts['caption_length'] ?? 30));
        $hc = max(1, (int) ($opts['hashtag_count'] ?? 3));

        $emojiLine = $emoji
            ? 'MANDATORY: Include 1–2 relevant emojis in the heading.'
            : 'Do not use emojis in the heading.';

        $userInstructions = '';
        if (!empty($customPrompt)) {
            $userInstructions = "\nUSER CUSTOM INSTRUCTIONS:\n" . $customPrompt . "\n";
        }

        return <<<PROMPT
You are a social media content expert. The image shows multiple frames from a video.

Analyze the visual content and create ORIGINAL, high-quality content.
{$userInstructions}
CRITICAL INSTRUCTIONS:
- IGNORE any external filenames, titles, or metadata you might think you know. 
- Focus ONLY on the visual details present in the frames.
- **DO NOT REUSE** words from the filename or title. Use your own creative vocabulary.

LENGTH REQUIREMENTS (YOU MUST FOLLOW THESE EXACTLY OR YOU FAIL):
1. YouTube Heading: EXACTLY {$headingWords} words. {$emojiLine}
2. Social Caption: This MUST be a LONG, DETAILED paragraph of APPROXIMATELY {$captionWords} words. 
   - DO NOT be brief. 
   - Expand on the atmosphere, the action, and the feelings shown.
   - Use at least 4-5 descriptive sentences.
   - Include a hook and call-to-action. 
   - Do NOT include hashtags in this field.
3. Hashtags: EXACTLY {$hc} hashtags with # symbol.

FORMATTING:
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

            $heading = $json['youtube_heading'] ?? 'Video Analysis';
            $heading = $this->ensureHeadingEmoji($heading, $opts);

            // Clean caption - Strictly remove any hash tags that might have slipped into the caption
            $caption = $json['social_caption'] ?? '';
            // Remove all hashtags from the caption
            $caption = preg_replace('/#\w+/u', '', $caption);
            // Replace multiple spaces with a single space
            $caption = preg_replace('/\s+/u', ' ', $caption);
            $caption = trim($caption);

            return [
                'youtube_heading' => $heading,
                'social_caption' => $caption,
                'hashtags' => $tags,
            ];
        }
        $heading = $this->ensureHeadingEmoji('Video Content', $opts);
        return ['youtube_heading' => $heading, 'social_caption' => '', 'hashtags' => []];
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
