<?php

declare(strict_types=1);

namespace Modules\Authentication\Services;

use Modules\Authentication\Models\AuthenticationSetting;

/**
 * Service for managing authentication settings
 * Provides database-backed settings with fallback to config files
 */
class AuthenticationSettingsService
{
    /**
     * Get a setting value with fallback to config
     * Supports nested keys like 'features.social_auth.provider_configs.google'
     */
    public function get(string $key, $default = null)
    {
        // Try to get exact key first
        $dbValue = AuthenticationSetting::get($key);
        
        if ($dbValue !== null) {
            return $dbValue;
        }

        // If exact key not found, try to get nested structure by prefix
        // This handles cases like 'features.social_auth.provider_configs.google'
        // where we want the full nested array
        $allSettings = AuthenticationSetting::getAllAsArray();
        $keys = explode('.', $key);
        
        // Navigate through nested array
        $value = $allSettings;
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                // Key not found, try config fallback
                $configKey = 'authentication.' . $key;
                $configValue = config($configKey);
                
                if ($configValue !== null) {
                    return $configValue;
                }
                
                return $default;
            }
        }
        
        // Return the nested value if found
        if ($value !== $allSettings) {
            return $value;
        }

        // Fallback to config file
        $configKey = 'authentication.' . $key;
        $configValue = config($configKey);
        
        if ($configValue !== null) {
            return $configValue;
        }

        return $default;
    }

    /**
     * Set a setting value in database
     */
    public function set(string $key, $value, ?string $type = null): void
    {
        AuthenticationSetting::set($key, $value, $type);
    }

    /**
     * Get all settings as nested array (for API responses)
     */
    public function getAll(): array
    {
        $dbSettings = AuthenticationSetting::getAllAsArray();
        
        // Merge with config file as fallback
        $configPath = module_path('Authentication', 'config/module.php');
        $configSettings = file_exists($configPath) ? require $configPath : [];
        
        return array_replace_recursive($configSettings, $dbSettings);
    }

    /**
     * Update multiple settings at once
     */
    public function update(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                // Recursively set nested settings
                $this->updateNested($key, $value);
            } else {
                AuthenticationSetting::set($key, $value);
            }
        }
        
        // Clear cache
        AuthenticationSetting::clearCache();
    }

    /**
     * Update nested settings
     */
    private function updateNested(string $prefix, array $values): void
    {
        foreach ($values as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value) && !empty($value) && !$this->isAssociativeArray($value)) {
                // Keep arrays as-is (like providers array)
                AuthenticationSetting::set($fullKey, $value, 'array');
            } elseif (is_array($value)) {
                $this->updateNested($fullKey, $value);
            } else {
                AuthenticationSetting::set($fullKey, $value);
            }
        }
    }

    /**
     * Check if array is associative
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
