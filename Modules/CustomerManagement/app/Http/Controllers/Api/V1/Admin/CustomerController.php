<?php

declare(strict_types=1);

namespace Modules\CustomerManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\CustomerManagement\Actions\GetCustomerProfileAction;
use Modules\CustomerManagement\Actions\ListCustomersAction;
use Modules\CustomerManagement\DTOs\ListCustomersDTO;
use Modules\CustomerManagement\Http\Requests\ListCustomersRequest;
use Modules\CustomerManagement\Http\Resources\CustomerProfileResource;
use Modules\UserManagement\Http\Resources\UserResource;
use Modules\UserManagement\Models\User;

class CustomerController extends BaseApiController
{
    public function __construct(
        private ListCustomersAction $listCustomersAction,
        private GetCustomerProfileAction $getCustomerProfileAction
    ) {
    }

    /**
     * List customers (users with customer role).
     */
    public function index(ListCustomersRequest $request): JsonResponse
    {
        $this->authorize('viewAnyCustomers');

        $dto = ListCustomersDTO::fromArray($request->validated());
        $customers = $this->listCustomersAction->execute($dto);

        return $this->paginatedResource($customers, UserResource::class, 'Customers retrieved successfully');
    }

    /**
     * Show customer profile with aggregates.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('viewCustomerProfile', $user);

        $data = $this->getCustomerProfileAction->execute($user);

        return $this->success(
            new CustomerProfileResource([
                'user' => $data->user,
                'data' => $data,
            ]),
            'Customer profile retrieved successfully'
        );
    }
}
