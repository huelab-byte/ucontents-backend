<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Authentication\Services\TwoFactorService;

/**
 * Middleware to ensure users with required 2FA have it enabled
 * Blocks access to protected routes if 2FA is required but not enabled
 */
class RequireTwoFactorSetup
{
    public function __construct(
        private TwoFactorService $twoFactorService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Check if 2FA is required for this user
        $isRequired = $this->twoFactorService->is2FARequired($user);
        $isEnabled = $this->twoFactorService->is2FAEnabled($user);

        // If 2FA is required but not enabled, block access
        if ($isRequired && !$isEnabled) {
            // Allow access to 2FA setup endpoints (these are needed to enable 2FA)
            $path = $request->path();
            $allowedPaths = [
                'api/v1/auth/2fa/setup',
                'api/v1/auth/2fa/enable',
                'api/v1/auth/2fa/status',
                'api/v1/auth/logout', // Allow logout
            ];
            
            $isAllowed = false;
            foreach ($allowedPaths as $allowedPath) {
                if (str_starts_with($path, $allowedPath)) {
                    $isAllowed = true;
                    break;
                }
            }
            
            if ($isAllowed) {
                return $next($request);
            }

            // Block access to all other endpoints
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is required. Please set it up to continue.',
                'requires_2fa_setup' => true,
            ], 403);
        }

        return $next($request);
    }
}
