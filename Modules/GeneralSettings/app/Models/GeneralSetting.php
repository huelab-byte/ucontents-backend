<?php

declare(strict_types=1);

namespace Modules\GeneralSettings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * General Setting Model
 */
class GeneralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'general_setting:';

    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get setting value by key with dot notation support
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->getValue();
        });
    }

    /**
     * Set setting value by key
     */
    public static function set(string $key, $value, ?string $type = null): void
    {
        // Determine type if not provided
        if ($type === null) {
            $type = static::getValueType($value);
        }

        // Convert value to string based on type
        $stringValue = match ($type) {
            'boolean' => ($value === true || $value === 'true' || $value === '1' || $value === 1) ? '1' : '0',
            'integer' => (string) $value,
            'array' => json_encode($value),
            default => (string) $value,
        };

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
            ]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);
        Cache::forget(self::CACHE_PREFIX . 'all');
    }

    /**
     * Get multiple settings by prefix (e.g., 'branding.site_name')
     */
    public static function getByPrefix(string $prefix): array
    {
        $cacheKey = self::CACHE_PREFIX . 'prefix:' . $prefix;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($prefix) {
            $settings = static::where('key', 'like', "{$prefix}.%")
                ->orWhere('key', $prefix)
                ->get();

            $result = [];
            foreach ($settings as $setting) {
                $key = str_replace("{$prefix}.", '', $setting->key);
                $result[$key] = $setting->getValue();
            }

            return $result;
        });
    }

    /**
     * Get all settings as nested array structure
     */
    public static function getAllAsArray(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $settings = static::all();
            $result = [];

            foreach ($settings as $setting) {
                $keys = explode('.', $setting->key);
                $value = $setting->getValue();

                // Build nested array
                $current = &$result;
                foreach ($keys as $index => $key) {
                    if ($index === count($keys) - 1) {
                        $current[$key] = $value;
                    } else {
                        if (!isset($current[$key]) || !is_array($current[$key])) {
                            $current[$key] = [];
                        }
                        $current = &$current[$key];
                    }
                }
            }

            return $result;
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'all');
        
        // Clear individual caches
        static::all()->each(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        });
    }

    /**
     * Get the actual value based on type
     */
    public function getValue()
    {
        return match ($this->type) {
            'boolean' => in_array($this->value, ['1', 'true', 'True', 'TRUE', 1, true], true),
            'integer' => (int) $this->value,
            'array' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }

    /**
     * Set the value attribute
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = is_array($value) ? json_encode($value) : (string) $value;
    }

    /**
     * Get value type
     */
    private static function getValueType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_array($value)) {
            return 'array';
        }
        return 'string';
    }
}
