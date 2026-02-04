<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\AiIntegration\Http\Requests\ExternalAiChatRequest;
use Modules\AiIntegration\Http\Requests\ExternalAiImageAnalyzeRequest;
use Modules\AiIntegration\Services\ExternalAiChatService;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Customer API Controller for AI Chat
 * 
 * Provides endpoints for customers to interact with the external AI service
 */
class AiChatController extends BaseApiController
{
    public function __construct(
        private ExternalAiChatService $chatService
    ) {
    }

    /**
     * Send a chat message to the AI
     * 
     * @param ExternalAiChatRequest $request
     * @return JsonResponse
     */
    public function chat(ExternalAiChatRequest $request): JsonResponse
    {
        $this->authorize('use_chat', ExternalAiChatService::class);

        $message = $request->validated('message');
        $maxTokens = $request->validated('max_tokens', 500);

        $result = $this->chatService->chat($message, (int) $maxTokens);

        if ($result['success']) {
            return $this->success([
                'message' => $result['response'],
                'response_time_ms' => $result['response_time_ms'],
            ], 'AI response generated successfully');
        }

        return $this->error($result['error'] ?? 'Failed to generate AI response', 500);
    }

    /**
     * Analyze an image with the AI
     * 
     * @param ExternalAiImageAnalyzeRequest $request
     * @return JsonResponse
     */
    public function analyzeImage(ExternalAiImageAnalyzeRequest $request): JsonResponse
    {
        $this->authorize('use_chat', ExternalAiChatService::class);

        $message = $request->validated('message', 'Describe this image in detail');
        $image = $request->file('image');

        // Store the image temporarily
        $tempPath = $image->store('temp/ai-analysis', 'local');
        $fullPath = Storage::disk('local')->path($tempPath);

        try {
            $result = $this->chatService->analyzeImage($message, $fullPath);

            // Clean up temp file
            Storage::disk('local')->delete($tempPath);

            if ($result['success']) {
                return $this->success([
                    'message' => $result['response'],
                    'response_time_ms' => $result['response_time_ms'],
                ], 'Image analyzed successfully');
            }

            return $this->error($result['error'] ?? 'Failed to analyze image', 500);
        } catch (\Exception $e) {
            // Clean up temp file on error
            Storage::disk('local')->delete($tempPath);
            throw $e;
        }
    }

    /**
     * Test the AI service connection
     * 
     * @return JsonResponse
     */
    public function testConnection(): JsonResponse
    {
        $result = $this->chatService->testConnection();

        if ($result['success']) {
            return $this->success([
                'connected' => true,
                'response_time_ms' => $result['response_time_ms'],
            ], 'AI service is connected');
        }

        return $this->success([
            'connected' => false,
            'error' => $result['error'] ?? 'Connection failed',
        ], 'AI service connection test completed');
    }
}
