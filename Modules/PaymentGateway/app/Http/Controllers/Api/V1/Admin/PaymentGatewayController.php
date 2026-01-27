<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Actions\ConfigureGatewayAction;
use Modules\PaymentGateway\Actions\DeletePaymentGatewayAction;
use Modules\PaymentGateway\DTOs\ConfigureGatewayDTO;
use Modules\PaymentGateway\Http\Requests\CreatePaymentGatewayRequest;
use Modules\PaymentGateway\Http\Requests\UpdatePaymentGatewayRequest;
use Modules\PaymentGateway\Http\Requests\ListPaymentGatewaysRequest;
use Modules\PaymentGateway\Http\Resources\PaymentGatewayResource;
use Modules\PaymentGateway\Models\PaymentGateway;
use Modules\PaymentGateway\Services\PaymentGatewayQueryService;

class PaymentGatewayController extends BaseApiController
{
    public function __construct(
        private ConfigureGatewayAction $configureGatewayAction,
        private DeletePaymentGatewayAction $deletePaymentGatewayAction,
        private PaymentGatewayQueryService $queryService
    ) {
    }

    /**
     * List all payment gateways
     */
    public function index(ListPaymentGatewaysRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentGateway::class);

        $validated = $request->validated();
        $filters = array_filter([
            'is_active' => isset($validated['is_active']) ? filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN) : null,
            'name' => $validated['name'] ?? null,
        ], fn($v) => $v !== null);

        $gateways = $this->queryService->listWithFilters($filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($gateways, PaymentGatewayResource::class, 'Payment gateways retrieved successfully');
    }

    /**
     * Show a specific payment gateway
     */
    public function show(PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('view', $paymentGateway);

        return $this->success(
            new PaymentGatewayResource($paymentGateway),
            'Payment gateway retrieved successfully'
        );
    }

    /**
     * Configure a payment gateway
     */
    public function store(CreatePaymentGatewayRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentGateway::class);

        $dto = ConfigureGatewayDTO::fromArray($request->validated());
        $gateway = $this->configureGatewayAction->execute($dto, $request->user()?->id);

        return $this->created(
            new PaymentGatewayResource($gateway),
            'Payment gateway configured successfully'
        );
    }

    /**
     * Update a payment gateway configuration
     */
    public function update(UpdatePaymentGatewayRequest $request, PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('update', $paymentGateway);

        // Merge existing gateway data with request data for update
        $validated = $request->validated();
        
        // Always use existing gateway name (name shouldn't change)
        $validated['name'] = $paymentGateway->name;
        
        // If credentials are not provided or empty, set to null so action keeps existing
        if (!isset($validated['credentials']) || empty($validated['credentials'])) {
            $validated['credentials'] = null;
        }
        
        $dto = ConfigureGatewayDTO::fromArray($validated);
        $gateway = $this->configureGatewayAction->execute($dto, $request->user()?->id);

        return $this->success(
            new PaymentGatewayResource($gateway),
            'Payment gateway updated successfully'
        );
    }

    /**
     * Delete a payment gateway
     */
    public function destroy(PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('delete', $paymentGateway);

        $this->deletePaymentGatewayAction->execute($paymentGateway);

        return $this->success(null, 'Payment gateway deleted successfully');
    }
}
