<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Modules\AiIntegration\Actions\CallAiModelAction;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\AiIntegration\Http\Requests\CallAiModelRequest;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Customer API Controller for calling AI models
 */
class AiModelController extends BaseApiController
{
    public function __construct(
        private CallAiModelAction $callAiModelAction
    ) {
    }

    /**
     * Call an AI model
     */
    public function call(CallAiModelRequest $request): JsonResponse
    {
        $dto = AiModelCallDTO::fromArray(
            array_merge($request->validated(), [
                'module' => $request->input('module'),
                'feature' => $request->input('feature'),
            ])
        );

        $response = $this->callAiModelAction->execute($dto, $request->user()?->id);

        return $this->success($response, 'AI model called successfully');
    }
}
