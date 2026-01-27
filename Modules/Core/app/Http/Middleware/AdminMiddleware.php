<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Middleware
 * 
 * Ensures that only authenticated users with admin role can access the route.
 * This middleware should be used for admin-only endpoints.
 */
class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isDebug = config('app.debug');

        if (!$user) {
            if ($isDebug) {
                \Log::warning('AdminMiddleware: No authenticated user', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login.',
            ], 401);
        }

        // Get a fresh user instance to ensure we have latest role data
        $freshUser = \Modules\UserManagement\Models\User::with('roles')->find($user->id);
        
        if (!$freshUser) {
            \Log::error('AdminMiddleware: User not found', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Check if user is admin (has admin or super_admin role)
        $isAdmin = $freshUser->roles()->whereIn('slug', ['super_admin', 'admin'])->exists();
        
        if (!$isAdmin) {
            if ($isDebug) {
                \Log::warning('AdminMiddleware: Access denied', [
                    'user_id' => $freshUser->id,
                    'roles' => $freshUser->roles->pluck('slug')->toArray(),
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action. Admin access required.',
            ], 403);
        }

        // Update request with fresh user
        $request->setUserResolver(function () use ($freshUser) {
            return $freshUser;
        });

        return $next($request);
    }
}
