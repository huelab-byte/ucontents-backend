<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Adapters;

use Anthropic\Client;
use Anthropic\Messages\MessageParam;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\AiIntegration\Models\AiApiKey;

/**
 * Anthropic (Claude) adapter
 */
class AnthropicAdapter implements ProviderAdapterInterface
{
    public function callModel(AiApiKey $apiKey, AiModelCallDTO $dto): array
    {
        $client = new Client(
            apiKey: $apiKey->getDecryptedApiKey()
        );

        $settings = array_merge([
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ], $dto->settings ?? []);

        // Build message content - support vision with images
        $messageContent = $this->buildMessageContent($dto);

        $response = $client->messages->create([
            'model' => $dto->model,
            'messages' => [
                MessageParam::with(role: 'user', content: $messageContent),
            ],
            'temperature' => $settings['temperature'],
            'max_tokens' => $settings['max_tokens'],
        ]);

        // Extract content - response structure may vary
        $content = '';
        if (is_array($response->content)) {
            $content = $response->content[0]->text ?? '';
        } elseif (is_string($response->content)) {
            $content = $response->content;
        }

        $usage = $response->usage ?? null;

        return [
            'content' => $content,
            'prompt_tokens' => $usage->inputTokens ?? 0,
            'completion_tokens' => $usage->outputTokens ?? 0,
            'total_tokens' => ($usage->inputTokens ?? 0) + ($usage->outputTokens ?? 0),
            'model' => $response->model ?? $dto->model,
        ];
    }

    /**
     * Build message content, supporting vision with images
     */
    private function buildMessageContent(AiModelCallDTO $dto): string|array
    {
        // Check if there's an image in metadata
        $image = $dto->metadata['image'] ?? null;
        $imageFormat = $dto->metadata['image_format'] ?? 'base64';
        
        if (!$image) {
            // No image, return plain text
            return $dto->prompt;
        }

        // Build vision-compatible message with image (Claude 3 format)
        $content = [];
        
        if ($imageFormat === 'base64') {
            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => 'image/jpeg',
                    'data' => $image,
                ],
            ];
        } elseif ($imageFormat === 'url') {
            // Claude doesn't support URL images directly, would need to download
            $content[] = [
                'type' => 'text',
                'text' => '[Image URL provided but not supported]',
            ];
        }
        
        $content[] = [
            'type' => 'text',
            'text' => $dto->prompt,
        ];

        return $content;
    }
}
