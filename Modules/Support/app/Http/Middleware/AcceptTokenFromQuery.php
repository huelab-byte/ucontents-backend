<?php

declare(strict_types=1);

namespace Modules\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to accept Sanctum token from query string for download endpoints
 * This allows direct browser downloads without requiring Authorization header
 */
class AcceptTokenFromQuery
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If token is in query string and not in Authorization header, add it
        $token = $request->query('token');
        $bearerToken = $request->bearerToken();
        
        if ($token && !$bearerToken) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
            Log::debug('AcceptTokenFromQuery: Token extracted from query string', [
                'url' => $request->fullUrl(),
                'has_token' => !empty($token),
            ]);
        } elseif (!$token && !$bearerToken) {
            Log::warning('AcceptTokenFromQuery: No token found in query string or Authorization header', [
                'url' => $request->fullUrl(),
            ]);
        }

        return $next($request);
    }
}
