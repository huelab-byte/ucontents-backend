# BaseApiController Usage Guide

## Overview

The `BaseApiController` provides standardized response methods for all API controllers in the application. All API controllers should extend this class.

## Location

`Modules/Core/Http/Controllers/Api/BaseApiController.php`

## Usage Examples

### Basic Success Response

```php
use Modules\Core\Http\Controllers\Api\BaseApiController;

class UserController extends BaseApiController
{
    public function show(User $user)
    {
        return $this->success(
            new UserResource($user),
            'User retrieved successfully'
        );
    }
}
```

### Created Response (201)

```php
public function store(StoreUserRequest $request, CreateUserAction $action)
{
    $user = $action->execute(UserDTO::fromRequest($request));
    
    return $this->created(
        new UserResource($user),
        'User created successfully'
    );
}
```

### No Content Response (204)

```php
public function destroy(User $user)
{
    $this->deleteUserAction->execute($user);
    
    return $this->noContent();
}
```

### Paginated Response

```php
public function index(UserQuery $query)
{
    $users = $query->paginated();
    
    return $this->paginated(
        $users,
        'Users retrieved successfully'
    );
}
```

### Error Responses

```php
// Validation Error (422)
public function store(StoreUserRequest $request)
{
    // Validation is handled by FormRequest
    // If validation fails, Laravel automatically returns 422
}

// Unauthorized (401)
if (!auth()->check()) {
    return $this->unauthorized();
}

// Forbidden (403)
if (!$user->can('update', $resource)) {
    return $this->forbidden();
}

// Not Found (404)
$user = User::find($id);
if (!$user) {
    return $this->notFound('User not found');
}

// Custom Error (400)
return $this->error('Invalid operation', 400);

// Too Many Requests (429)
return $this->tooManyRequests('Rate limit exceeded', 60);
```

### Exception Handling

```php
public function store(StoreUserRequest $request, CreateUserAction $action)
{
    try {
        $user = $action->execute(UserDTO::fromRequest($request));
        return $this->created(new UserResource($user));
    } catch (\Exception $e) {
        return $this->handleException($e);
    }
}
```

## Available Methods

### Success Methods

- `success($data, $message, $code)` - Generic success response
- `created($data, $message)` - 201 Created response
- `noContent()` - 204 No Content response
- `paginated($data, $message)` - Paginated response

### Error Methods

- `error($message, $code, $errors)` - Generic error response
- `validationError($message, $errors)` - 422 Validation error
- `unauthorized($message)` - 401 Unauthorized
- `forbidden($message)` - 403 Forbidden
- `notFound($message)` - 404 Not Found
- `tooManyRequests($message, $retryAfter)` - 429 Too Many Requests

### Helper Methods

- `handleValidationException($exception)` - Format validation errors
- `handleException($exception, $message)` - Handle any exception

## Response Format

All responses follow the standard format defined in `docs/API_CONVENTIONS.md`:

**Success:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

**Paginated:**
```json
{
  "success": true,
  "message": "Success",
  "data": [ ... ],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```
