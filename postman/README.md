# Postman Collection for Social Management API

This directory contains Postman collections and environments for testing the Social Management API.

## Files

- **Social_Management_API.postman_collection.json** - Complete API collection with all endpoints
- **Local_Environment.postman_environment.json** - Environment variables for local development

## Import Instructions

1. Open Postman
2. Click **Import** button
3. Select both JSON files:
   - `Social_Management_API.postman_collection.json`
   - `Local_Environment.postman_environment.json`
4. Select the **Local Environment** from the environment dropdown

## Quick Start

### 1. Set Base URL
- The default base URL is `http://localhost:8000`
- Update it in the environment if your server runs on a different port

### 2. Login First
1. Go to **Authentication > Login**
2. Use demo credentials:
   - Email: `superadmin@example.com`
   - Password: `Password123!`
3. The auth token will be automatically saved to the environment

### 3. Test Endpoints
- All authenticated endpoints will use the saved token automatically
- The token is set via Bearer authentication

## Demo Users

All users have the password: `Password123!`

- **Super Admin**: `superadmin@example.com`
- **Admin**: `admin@example.com`
- **Manager**: `manager@example.com`
- **Customer**: `customer@example.com`
- **Jane**: `jane@example.com`
- **Test User**: `test@example.com` (unverified)

## Endpoint Categories

### Core
- `GET /api/health` - Health check (public)

### Authentication (Public)
- `POST /api/v1/auth/login` - Login with email/password
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/logout` - Logout (authenticated)
- `POST /api/v1/auth/magic-link/request` - Request magic link
- `POST /api/v1/auth/magic-link/verify` - Verify magic link
- `POST /api/v1/auth/otp/request` - Request OTP code
- `POST /api/v1/auth/otp/verify` - Verify OTP code
- `POST /api/v1/auth/password/reset/request` - Request password reset
- `POST /api/v1/auth/password/reset` - Reset password

### User Management - Admin
- `GET /api/v1/admin/users` - List users
- `POST /api/v1/admin/users` - Create user
- `GET /api/v1/admin/users/{id}` - Get user
- `PUT /api/v1/admin/users/{id}` - Update user
- `DELETE /api/v1/admin/users/{id}` - Delete user
- `GET /api/v1/admin/roles` - List roles
- `POST /api/v1/admin/roles` - Create role
- `GET /api/v1/admin/roles/{id}` - Get role
- `PUT /api/v1/admin/roles/{id}` - Update role
- `DELETE /api/v1/admin/roles/{id}` - Delete role
- `GET /api/v1/admin/roles/permissions/list` - List permissions

### User Management - Customer
- `GET /api/v1/customer/profile` - Get own profile
- `PUT /api/v1/customer/profile` - Update own profile

### Client Management - Admin
- `GET /api/v1/admin/clients` - List API clients
- `POST /api/v1/admin/clients` - Create API client
- `GET /api/v1/admin/clients/{id}` - Get API client
- `PUT /api/v1/admin/clients/{id}` - Update API client
- `DELETE /api/v1/admin/clients/{id}` - Delete API client
- `GET /api/v1/admin/clients/{id}/keys` - List API keys
- `POST /api/v1/admin/clients/{id}/keys` - Generate API key
- `GET /api/v1/admin/clients/{id}/keys/{keyId}` - Get API key
- `POST /api/v1/admin/clients/{id}/keys/{keyId}/revoke` - Revoke API key
- `POST /api/v1/admin/clients/{id}/keys/{keyId}/rotate` - Rotate API key
- `GET /api/v1/admin/clients/{id}/keys/{keyId}/activity` - Get API key activity

## Environment Variables

The collection uses these variables:

- `base_url` - API base URL (default: http://localhost:8000)
- `auth_token` - Sanctum authentication token (auto-set on login)
- `user_id` - Current user ID (auto-set on login)
- `role_id` - Role ID for testing
- `api_client_id` - API client ID
- `api_key_id` - API key ID
- `api_public_key` - API public key (auto-set on key generation)
- `api_secret_key` - API secret key (auto-set on key generation)
- `magic_link_token` - Magic link token
- `password_reset_token` - Password reset token

## Authentication

### Sanctum (Web/Mobile)
All authenticated endpoints use Bearer token authentication:
```
Authorization: Bearer {auth_token}
```

The token is automatically saved when you login and used in subsequent requests.

### JWT (Public API)
For public API endpoints (future):
```
Authorization: Bearer {jwt_token}
X-API-Key: {api_public_key}
X-API-Secret: {api_secret_key}
```

## Response Format

All responses follow this format:

### Success
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

### Paginated
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [ ... ],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

## Testing Workflow

1. **Start Server**: `php artisan serve`
2. **Run Migrations**: `php artisan migrate`
3. **Seed Database**: `php artisan db:seed`
4. **Import Collection**: Import both JSON files into Postman
5. **Login**: Use the Login endpoint to get auth token
6. **Test Endpoints**: All other endpoints will use the saved token

## Notes

- The Login endpoint automatically saves the token to the environment
- API key generation endpoints automatically save keys to the environment
- All admin endpoints require admin role (super_admin or admin)
- System roles (super_admin, admin, customer, guest) cannot be deleted
- API key secrets are only shown once on generation/rotation
