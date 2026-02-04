<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for calling external AI chat API
 * 
 * This service connects to the custom LLaVA-based AI server
 * at https://gpt.ucontents.com
 */
class ExternalAiChatService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('aiintegration.external_chat.base_url', 'https://gpt.ucontents.com');
        $this->apiKey = config('aiintegration.external_chat.api_key', 'pk_prod_tbQFGQFKIb8SeyvaPqAJX7nrXk7ZRlJU');
    }

    /**
     * Send a text-only chat message
     */
    public function chat(string $prompt, int $maxTokens = 500): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/generate", [
                    'prompt' => $prompt,
                    'max_tokens' => $maxTokens,
                ]);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $rawResponse = $data['response'] ?? '';
                $cleanedResponse = $this->cleanResponse($rawResponse, $prompt);
                
                return [
                    'success' => true,
                    'response' => $cleanedResponse,
                    'response_time_ms' => $responseTime,
                ];
            }

            Log::error('External AI chat error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get response from AI service',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            
            Log::error('External AI chat exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
            ];
        }
    }

    /**
     * Send a message with an image for analysis
     */
    public function analyzeImage(string $prompt, string $imagePath, int $maxTokens = 500): array
    {
        $startTime = microtime(true);

        try {
            if (!file_exists($imagePath)) {
                throw new \Exception("Image file not found: {$imagePath}");
            }

            $response = Http::timeout(180)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->attach('file', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->baseUrl}/analyze", [
                    'prompt' => $prompt,
                ]);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $rawResponse = $data['response'] ?? '';
                $cleanedResponse = $this->cleanResponse($rawResponse, $prompt);
                
                return [
                    'success' => true,
                    'response' => $cleanedResponse,
                    'response_time_ms' => $responseTime,
                ];
            }

            Log::error('External AI image analysis error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to analyze image',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            
            Log::error('External AI image analysis exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
            ];
        }
    }

    /**
     * Test the connection to the AI service
     */
    public function testConnection(): array
    {
        return $this->chat('Hello, please respond with a brief greeting.', 50);
    }

    /**
     * Clean the AI response by removing the prompt from the beginning
     * 
     * The LLaVA/Mistral models sometimes echo back the prompt at the start of the response.
     * This method strips out common patterns:
     * - Prompt at the very beginning
     * - "[INST] ... [/INST]" format (Mistral)
     * - "USER: ... ASSISTANT:" format
     * - Various conversation markers
     */
    private function cleanResponse(string $response, string $prompt): string
    {
        if (empty($response)) {
            return $response;
        }

        $cleaned = $response;

        // Pattern 1: Remove Mistral's "[INST] ... [/INST]" format
        if (preg_match('/\[\/INST\]\s*(.*)$/s', $cleaned, $matches)) {
            $cleaned = $matches[1];
        }

        // Pattern 2: Remove "[INST] prompt [/INST]" if still present at beginning
        if (preg_match('/^\s*\[INST\].*?\[\/INST\]\s*/is', $cleaned, $matches)) {
            $cleaned = substr($cleaned, strlen($matches[0]));
        }

        // Pattern 3: Remove "USER: ... ASSISTANT:" wrapper if present
        if (preg_match('/^USER:\s*.*?ASSISTANT:\s*/is', $cleaned, $matches)) {
            $cleaned = substr($cleaned, strlen($matches[0]));
        }

        // Pattern 4: Remove the exact prompt if it appears at the beginning
        $promptEscaped = preg_quote($prompt, '/');
        if (preg_match('/^' . $promptEscaped . '\s*/i', $cleaned, $matches)) {
            $cleaned = substr($cleaned, strlen($matches[0]));
        }

        // Pattern 5: Handle case where prompt is followed by common separators
        $separators = [':', '.', '!', '?', "\n", ' - '];
        foreach ($separators as $sep) {
            $prefixWithSep = $prompt . $sep;
            if (stripos($cleaned, $prefixWithSep) === 0) {
                $cleaned = substr($cleaned, strlen($prefixWithSep));
                break;
            }
        }

        // Pattern 6: Remove common AI response prefixes (keep these as they're natural)
        $prefixes = [
            'Sure, here is',
            'Sure! Here is',
            'Here is',
            'Here\'s',
            'Certainly!',
            'Of course!',
        ];
        foreach ($prefixes as $prefix) {
            if (stripos(trim($cleaned), $prefix) === 0) {
                // Keep these prefixes as they're part of a natural response
                break;
            }
        }

        // Trim any leading/trailing whitespace and newlines
        $cleaned = trim($cleaned);

        return $cleaned;
    }
}

