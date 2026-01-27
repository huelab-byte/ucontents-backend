<?php

declare(strict_types=1);

namespace Modules\Authentication\Helpers;

use Modules\Authentication\Services\AuthenticationSettingsService;

/**
 * Helper class for token-related operations
 * Provides dynamic token expiry settings from database
 */
class TokenHelper
{
    /**
     * Get Sanctum token expiry in minutes from database settings
     */
    public static function getSanctumExpiry(): int
    {
        $settingsService = app(AuthenticationSettingsService::class);
        return (int) $settingsService->get('token.sanctum_expiry', 1440); // Default 24 hours
    }

    /**
     * Get JWT token expiry in minutes from database settings
     */
    public static function getJwtExpiry(): int
    {
        $settingsService = app(AuthenticationSettingsService::class);
        return (int) $settingsService->get('token.jwt_expiry', 60); // Default 1 hour
    }

    /**
     * Get refresh token expiry in minutes from database settings
     */
    public static function getRefreshExpiry(): int
    {
        $settingsService = app(AuthenticationSettingsService::class);
        return (int) $settingsService->get('token.refresh_expiry', 43200); // Default 30 days
    }

    /**
     * Get JWT TTL for use with JWT library
     * This can be used to dynamically set JWT TTL before token generation
     */
    public static function getJwtTtl(): int
    {
        return self::getJwtExpiry();
    }

    /**
     * Get JWT Refresh TTL for use with JWT library
     */
    public static function getJwtRefreshTtl(): int
    {
        return self::getRefreshExpiry();
    }
}
