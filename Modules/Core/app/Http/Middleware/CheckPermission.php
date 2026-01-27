<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission Check Middleware
 * 
 * Checks if the authenticated user has the required permission(s).
 * Usage: 'permission:view_users' or 'permission:view_users|create_user' (OR logic)
 *        'permission:view_users,create_user' (AND logic - requires both)
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $permissions Permission(s) required (pipe-separated for OR, comma-separated for AND)
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();
        $isDebug = config('app.debug');

        if (!$user) {
            if ($isDebug) {
                \Log::warning('Permission Check: No authenticated user', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login.',
            ], 401);
        }

        // Get a fresh user instance from database to ensure we have latest data
        $freshUser = \Modules\UserManagement\Models\User::with('roles')->find($user->id);
        
        if (!$freshUser) {
            \Log::error('Permission Check: User not found', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }
        
        // Check if user is super admin (bypass all permission checks)
        $isSuperAdmin = $freshUser->roles()->where('slug', 'super_admin')->exists();
        
        if ($isSuperAdmin) {
            // Update the request with the fresh user instance
            $request->setUserResolver(function () use ($freshUser) {
                return $freshUser;
            });
            return $next($request);
        }
        
        // Use fresh user for permission checks
        $user = $freshUser;

        // Parse permissions - support both OR (|) and AND (,) logic
        $permissionList = [];
        
        // Check for AND logic (comma-separated)
        if (str_contains($permissions, ',')) {
            $permissionList = array_map('trim', explode(',', $permissions));
            $hasAllPermissions = true;
            
            foreach ($permissionList as $permission) {
                if (!$user->hasPermission(trim($permission))) {
                    $hasAllPermissions = false;
                    break;
                }
            }
            
            if (!$hasAllPermissions) {
                if ($isDebug) {
                    \Log::warning('Permission Check: Access denied (AND logic)', [
                        'user_id' => $user->id,
                        'required_permissions' => $permissionList,
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }
            
            return $next($request);
        }
        
        // OR logic (pipe-separated) - default behavior
        $permissionList = array_map('trim', explode('|', $permissions));
        
        foreach ($permissionList as $permission) {
            if ($user->hasPermission(trim($permission))) {
                return $next($request);
            }
        }

        // User doesn't have any of the required permissions
        if ($isDebug) {
            \Log::warning('Permission Check: Access denied (OR logic)', [
                'user_id' => $user->id,
                'required_permissions' => $permissionList,
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }
}
