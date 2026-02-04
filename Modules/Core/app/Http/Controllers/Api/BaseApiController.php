<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Base API Controller
 * 
 * All API controllers should extend this class.
 * Provides standardized response methods for consistent API responses.
 * 
 * @see docs/API_CONVENTIONS.md for response format standards
 */
class BaseApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Return a successful JSON response
     *
     * @param mixed $data Response data (can be JsonResource, array, model, etc.)
     * @param string $message Success message
     * @param int $code HTTP status code (default: 200)
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        // Handle JsonResource responses
        if ($data instanceof JsonResource) {
            $response = $data->response();
            $responseData = $response->getData(true);
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $responseData['data'] ?? $responseData,
            ], $code);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a created response (201)
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a no content response (204)
     *
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @param mixed $errors Validation errors or additional error details
     * @return JsonResponse
     */
    protected function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a validation error response (422)
     *
     * @param string $message Error message
     * @param mixed $errors Validation errors
     * @return JsonResponse
     */
    protected function validationError(string $message = 'The given data was invalid.', $errors = null): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Return an unauthorized response (401)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthenticated. Please login.'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden response (403)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'You are not authorized to perform this action.'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a not found response (404)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return a paginated JSON response
     *
     * @param LengthAwarePaginator $data Paginated data
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function paginated(LengthAwarePaginator $data, string $message = 'Success'): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ],
        ];

        return response()->json($response);
    }

    /**
     * Return a paginated JSON response where items are transformed
     * using a JsonResource collection.
     *
     * @param LengthAwarePaginator $paginator Paginated data
     * @param class-string<JsonResource> $resourceClass Resource class to transform items
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function paginatedResource(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'Success'
    ): JsonResponse {
        $resourceCollection = $resourceClass::collection($paginator->getCollection());
        $resourceData = $resourceCollection->resolve();

        $response = [
            'success' => true,
            'message' => $message,
            'data' => $resourceData,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];

        return response()->json($response);
    }

    /**
     * Handle validation exception and return formatted error response
     *
     * @param ValidationException $exception
     * @return JsonResponse
     */
    protected function handleValidationException(ValidationException $exception): JsonResponse
    {
        return $this->validationError(
            'The given data was invalid.',
            $exception->errors()
        );
    }

    /**
     * Return a too many requests response (429)
     *
     * @param string $message Error message
     * @param int $retryAfter Seconds to wait before retrying
     * @return JsonResponse
     */
    protected function tooManyRequests(string $message = 'Too many requests. Please try again later.', int $retryAfter = 60): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }

    /**
     * Handle exceptions and return appropriate error response
     *
     * @param \Throwable $exception
     * @param string|null $message Custom error message
     * @return JsonResponse
     */
    protected function handleException(\Throwable $exception, ?string $message = null): JsonResponse
    {
        // Log the exception
        Log::error('API Exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($exception);
        }

        // Return generic error response
        $errorMessage = $message ?? 'An error occurred while processing your request.';
        
        // Include exception message to help debug PROD issues temporarily
        $errorMessage .= ' Error: ' . $exception->getMessage();
        
        if (config('app.debug')) {
            $errorMessage .= ' File: ' . $exception->getFile() . ' Line: ' . $exception->getLine();
        }

        return $this->error($errorMessage, 500);
    }
}
