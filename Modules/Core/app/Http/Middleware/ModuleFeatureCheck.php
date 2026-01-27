<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Module Feature Check Middleware
 * 
 * Checks if a module feature is enabled before allowing access to the route.
 * Usage: 'module.feature:module_name' or 'module.feature:module_name.feature_name'
 */
class ModuleFeatureCheck
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $module Module name (e.g., 'user_management' or 'user_management.user_crud')
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        // Parse module and feature from the parameter
        $parts = explode('.', $module);
        $moduleName = $parts[0];
        $featureName = $parts[1] ?? null;

        // Convert module name to lowercase for config lookup
        $moduleNameLower = strtolower($moduleName);

        // Check module configuration - try multiple possible config paths
        $enabled = false;
        
        if ($featureName) {
            // Check for feature-specific config: module.module.features.feature.enabled
            $enabled = config("{$moduleNameLower}.module.features.{$featureName}.enabled", false);
            
            // Fallback: module.features.feature.enabled
            if ($enabled === false) {
                $enabled = config("{$moduleNameLower}.features.{$featureName}.enabled", false);
            }
        } else {
            // Check for module-level config: module.module.enabled
            $enabled = config("{$moduleNameLower}.module.enabled", false);
            
            // Fallback: module.enabled (for backward compatibility)
            if ($enabled === false) {
                $enabled = config("{$moduleNameLower}.enabled", false);
            }
            
            // If still not found, try loading directly from module config file
            if ($enabled === false) {
                $configPath = module_path($moduleName, 'config/module.php');
                if (file_exists($configPath)) {
                    $moduleConfig = require $configPath;
                    $enabled = $moduleConfig['enabled'] ?? false;
                }
            }
        }

        if (!$enabled) {
            return response()->json([
                'success' => false,
                'message' => 'This feature is currently disabled.',
            ], 403);
        }

        return $next($request);
    }
}
