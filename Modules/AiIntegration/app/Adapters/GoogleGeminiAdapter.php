<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Adapters;

use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Enums\MimeType;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\AiIntegration\Models\AiApiKey;

/**
 * Google Gemini adapter
 */
class GoogleGeminiAdapter implements ProviderAdapterInterface
{
    public function callModel(AiApiKey $apiKey, AiModelCallDTO $dto): array
    {
        $client = new Client($apiKey->getDecryptedApiKey());
        
        $settings = array_merge([
            'temperature' => 0.7,
            'maxOutputTokens' => 1000,
        ], $dto->settings ?? []);

        $model = $client->generativeModel($dto->model);
        
        // Build content parts - support vision with images
        $parts = $this->buildContentParts($dto);
        
        $response = $model->generateContent(...$parts);

        $content = $response->text() ?? '';
        
        // Note: Token usage might not be available in all SDK versions
        $usageMetadata = $response->usageMetadata ?? null;

        return [
            'content' => $content,
            'prompt_tokens' => $usageMetadata->promptTokenCount ?? 0,
            'completion_tokens' => $usageMetadata->candidatesTokenCount ?? 0,
            'total_tokens' => $usageMetadata->totalTokenCount ?? 0,
            'model' => $dto->model,
        ];
    }

    /**
     * Build content parts, supporting vision with images
     */
    private function buildContentParts(AiModelCallDTO $dto): array
    {
        $parts = [];
        
        // Check if there's an image in metadata
        $image = $dto->metadata['image'] ?? null;
        $imageFormat = $dto->metadata['image_format'] ?? 'base64';
        
        if ($image && $imageFormat === 'base64') {
            // Add image part first
            $parts[] = new ImagePart(
                MimeType::IMAGE_JPEG,
                $image
            );
        }
        
        // Add text part
        $parts[] = new TextPart($dto->prompt);
        
        return $parts;
    }
}
