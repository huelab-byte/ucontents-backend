<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Actions\ExecutePayPalPaymentAction;
use Modules\PaymentGateway\Actions\ProcessPaymentAction;
use Modules\PaymentGateway\DTOs\ProcessPaymentDTO;
use Modules\PaymentGateway\Http\Requests\ExecutePayPalPaymentRequest;
use Modules\PaymentGateway\Http\Requests\ListPaymentsRequest;
use Modules\PaymentGateway\Http\Requests\ProcessPaymentRequest;
use Modules\PaymentGateway\Http\Resources\PaymentResource;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Services\PaymentQueryService;

class PaymentController extends BaseApiController
{
    public function __construct(
        private ProcessPaymentAction $processPaymentAction,
        private ExecutePayPalPaymentAction $executePayPalPaymentAction,
        private PaymentQueryService $queryService
    ) {
    }

    /**
     * List user's payments
     */
    public function index(ListPaymentsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $user = $request->user();
        $validated = $request->validated();

        $filters = array_filter([
            'status' => $validated['status'] ?? null,
            'invoice_id' => $validated['invoice_id'] ?? null,
        ], fn($v) => $v !== null);

        $payments = $this->queryService->listForUserWithFilters($user->id, $filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($payments, PaymentResource::class, 'Payments retrieved successfully');
    }

    /**
     * Show a specific payment
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['invoice', 'paymentGateway', 'refunds']);

        return $this->success(
            new PaymentResource($payment),
            'Payment retrieved successfully'
        );
    }

    /**
     * Process a payment
     */
    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $dto = ProcessPaymentDTO::fromArray($data);
        $payment = $this->processPaymentAction->execute($dto);

        // If payment requires approval (e.g., PayPal), return approval URL
        if ($payment->status === 'pending' && isset($payment->metadata['approval_url'])) {
            return $this->success(
                new PaymentResource($payment->load('invoice')),
                'Payment requires approval',
                202
            );
        }

        return $this->created(
            new PaymentResource($payment->load('invoice')),
            'Payment processed successfully'
        );
    }

    /**
     * Execute a PayPal payment (after user approval)
     */
    public function executePayPal(ExecutePayPalPaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payerId = $request->validated('payer_id');

        try {
            $payment = $this->executePayPalPaymentAction->execute($payment, $payerId);

            return $this->success(
                new PaymentResource($payment->load('invoice')),
                'Payment executed successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
