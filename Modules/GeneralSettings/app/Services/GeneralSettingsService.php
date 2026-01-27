<?php

declare(strict_types=1);

namespace Modules\GeneralSettings\Services;

use Modules\GeneralSettings\Models\GeneralSetting;

/**
 * Service for managing general settings
 * Provides database-backed settings with fallback to config files
 */
class GeneralSettingsService
{
    /**
     * Get a setting value with fallback to config
     * Supports nested keys like 'branding.site_name'
     */
    public function get(string $key, $default = null)
    {
        // Try to get exact key first
        $dbValue = GeneralSetting::get($key);
        
        if ($dbValue !== null) {
            return $dbValue;
        }

        // If exact key not found, try to get nested structure by prefix
        $allSettings = GeneralSetting::getAllAsArray();
        $keys = explode('.', $key);
        
        // Navigate through nested array
        $value = $allSettings;
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                // Key not found, try config fallback
                $configKey = 'generalsettings.' . $key;
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
        $configKey = 'generalsettings.' . $key;
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
        GeneralSetting::set($key, $value, $type);
    }

    /**
     * Get all settings as nested array (for API responses)
     */
    public function getAll(): array
    {
        $dbSettings = GeneralSetting::getAllAsArray();
        
        // Merge with config file as fallback
        $configPath = module_path('GeneralSettings', 'config/module.php');
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
                GeneralSetting::set($key, $value);
            }
        }
        
        // Clear cache
        GeneralSetting::clearCache();
    }

    /**
     * Update nested settings
     */
    private function updateNested(string $prefix, array $values): void
    {
        foreach ($values as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value) && !empty($value) && !$this->isAssociativeArray($value)) {
                // Keep arrays as-is (like social_links array)
                GeneralSetting::set($fullKey, $value, 'array');
            } elseif (is_array($value)) {
                $this->updateNested($fullKey, $value);
            } else {
                GeneralSetting::set($fullKey, $value);
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
