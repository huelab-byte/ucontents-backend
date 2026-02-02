<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Modules\Authentication\Actions\SocialAuthAction;
use Modules\Authentication\Services\AuthenticationSettingsService;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Social Authentication Controller
 */
class SocialAuthController extends BaseApiController
{
    private const ALLOWED_PROVIDERS = ['google', 'facebook', 'tiktok'];

    public function __construct(
        private SocialAuthAction $socialAuthAction,
        private AuthenticationSettingsService $settingsService
    ) {
    }

    /**
     * Redirect to OAuth provider
     */
    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        // Validate provider
        if (!in_array($provider, self::ALLOWED_PROVIDERS)) {
            return $this->error('Invalid provider', 400);
        }

        // Check if social auth is enabled
        $socialAuthEnabled = $this->settingsService->get('features.social_auth.enabled', false);
        if (!$socialAuthEnabled) {
            return $this->error('Social authentication is disabled', 403);
        }

        // Check if provider is enabled
        $enabledProviders = $this->settingsService->get('features.social_auth.providers', []);
        if (!in_array($provider, $enabledProviders)) {
            return $this->error("{$provider} authentication is not enabled", 403);
        }

        // Get provider config
        $config = $this->settingsService->get("features.social_auth.provider_configs.{$provider}", []);
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;
        $mode = $config['mode'] ?? null; // For TikTok: 'sandbox' or 'live'

        if (!$clientId || !$clientSecret) {
            return $this->error("{$provider} is not properly configured", 400);
        }

        try {
            // TikTok: use frontend callback (fixed URL for live app). Other providers: backend callback.
            $callbackUrl = $this->getSocialAuthCallbackUrl($provider);

            Log::info("Social auth redirect for {$provider}", [
                'callback_url' => $callbackUrl,
                'mode' => $mode ?? 'default',
            ]);

            return $this->getSocialiteDriver($provider, $clientId, $clientSecret, $callbackUrl, $mode)
                ->stateless()
                ->redirect();
        } catch (\Exception $e) {
            Log::error("Social auth redirect failed for {$provider}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->error('Failed to initiate social authentication: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle OAuth callback
     * Can be called directly by OAuth provider or by frontend with code parameter
     */
    public function callback(Request $request, string $provider): RedirectResponse|JsonResponse
    {
        // Validate provider
        if (!in_array($provider, self::ALLOWED_PROVIDERS)) {
            if ($request->expectsJson()) {
                return $this->error('Invalid provider', 400);
            }
            return $this->redirectWithError('Invalid provider');
        }

        // Check if social auth is enabled
        $socialAuthEnabled = $this->settingsService->get('features.social_auth.enabled', false);
        if (!$socialAuthEnabled) {
            if ($request->expectsJson()) {
                return $this->error('Social authentication is disabled', 403);
            }
            return $this->redirectWithError('Social authentication is disabled');
        }

        // Check if provider is enabled
        $enabledProviders = $this->settingsService->get('features.social_auth.providers', []);
        if (!in_array($provider, $enabledProviders)) {
            if ($request->expectsJson()) {
                return $this->error("{$provider} authentication is not enabled", 403);
            }
            return $this->redirectWithError("{$provider} authentication is not enabled");
        }

        // Get provider config
        $config = $this->settingsService->get("features.social_auth.provider_configs.{$provider}", []);
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;
        $mode = $config['mode'] ?? null; // For TikTok: 'sandbox' or 'live'

        if (!$clientId || !$clientSecret) {
            if ($request->expectsJson()) {
                return $this->error("{$provider} is not properly configured", 400);
            }
            return $this->redirectWithError("{$provider} is not properly configured");
        }

        try {
            $callbackUrl = $this->getSocialAuthCallbackUrl($provider);

            // Configure Socialite dynamically and get user
            // Use stateless() to avoid session requirements
            // Socialite will automatically read the 'code' parameter from the request
            $socialiteUser = $this->getSocialiteDriver($provider, $clientId, $clientSecret, $callbackUrl, $mode)
                ->stateless()
                ->user();

            // Handle authentication (login if exists, register if new)
            $result = $this->socialAuthAction->execute($provider, $socialiteUser);

            $frontendUrl = $this->normalizeFrontendUrl(config('app.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000'))));
            $token = $result['token'];
            $redirectUrl = $frontendUrl . "/auth/social/callback?token={$token}";

            return redirect($redirectUrl);
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error("Social auth invalid state for {$provider}: " . $e->getMessage());
            if ($request->expectsJson()) {
                return $this->error('Authentication session expired. Please try again.', 400);
            }
            return $this->redirectWithError('Authentication session expired. Please try again.');
        } catch (\Exception $e) {
            Log::error("Social auth callback failed for {$provider}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            if ($request->expectsJson()) {
                return $this->error('Authentication failed: ' . $e->getMessage(), 500);
            }
            return $this->redirectWithError('Authentication failed. Please try again.');
        }
    }

    /**
     * Get configured Socialite driver for a provider
     * 
     * @param string $provider Provider name (google, facebook, tiktok)
     * @param string $clientId OAuth client ID
     * @param string $clientSecret OAuth client secret
     * @param string $redirectUrl Callback redirect URL
     * @param string|null $mode For TikTok: 'sandbox' or 'live', null for other providers
     */
    private function getSocialiteDriver(string $provider, string $clientId, string $clientSecret, string $redirectUrl, ?string $mode = null)
    {
        // Set config dynamically using both methods to ensure it's available
        $config = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect' => $redirectUrl,
        ];

        // For TikTok, add mode configuration (sandbox or live)
        // Note: TikTok OAuth endpoints are the same for both sandbox and live modes
        // The mode difference is controlled in TikTok Developer Portal app settings
        // We store the mode here for tracking/documentation and to help users
        // ensure their configuration matches their TikTok Developer Portal settings
        if ($provider === 'tiktok' && $mode) {
            $config['mode'] = $mode; // 'sandbox' or 'live' - stored for reference
            
            // Log mode for debugging and verification
            Log::info("TikTok OAuth configuration", [
                'provider' => $provider,
                'mode' => $mode,
                'note' => 'Mode setting is for reference. Ensure your TikTok Developer Portal app is set to the same mode (Sandbox/Live)',
            ]);
        }

        // Set config using config helper
        config(["services.{$provider}" => $config]);
        
        // Also set directly in config repository to ensure it's available
        $configRepository = app()->make('config');
        $configRepository->set("services.{$provider}", $config);

        // Get the driver
        $driver = Socialite::driver($provider)
            ->redirectUrl($redirectUrl);

        // TikTok OAuth endpoints are the same regardless of sandbox/live mode
        // The mode difference is handled in TikTok Developer Portal settings
        // We store the mode for reference and to help users track their configuration

        return $driver;
    }

    /**
     * Configure Socialite provider dynamically (legacy method, kept for callback)
     */
    private function configureSocialite(string $provider, string $clientId, string $clientSecret): void
    {
        $config = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect' => '', // Will be set per request
        ];

        // Provider-specific configurations
        switch ($provider) {
            case 'google':
                config(['services.google' => $config]);
                app()->make('config')->set('services.google', $config);
                break;
            case 'facebook':
                config(['services.facebook' => $config]);
                app()->make('config')->set('services.facebook', $config);
                break;
            case 'tiktok':
                config(['services.tiktok' => $config]);
                app()->make('config')->set('services.tiktok', $config);
                break;
        }
    }

    /**
     * Callback URL for social auth: TikTok uses fixed frontend URL (live app); others use backend.
     */
    private function getSocialAuthCallbackUrl(string $provider): string
    {
        if ($provider === 'tiktok') {
            $fixed = config('app.tiktok_oauth_callback_url');
            if ($fixed !== null && $fixed !== '') {
                return rtrim($fixed, '/');
            }
            $base = $this->normalizeFrontendUrl(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')));
            return $base . '/app/tiktok/profile';
        }

        $appUrl = config('app.url', env('APP_URL', 'http://localhost:8000'));
        return rtrim($appUrl, '/') . "/api/v1/auth/social/{$provider}/callback";
    }

    private function normalizeFrontendUrl(string $url): string
    {
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return rtrim($url, '/');
        }
        $host = $parsed['host'];
        if (str_starts_with(strtolower($host), 'www.')) {
            $parsed['host'] = substr($host, 4);
            $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : 'http://';
            $url = $scheme . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '') . (isset($parsed['path']) ? $parsed['path'] : '') . (isset($parsed['query']) ? '?' . $parsed['query'] : '') . (isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '');
        }
        return rtrim($url, '/');
    }

    /**
     * Exchange OAuth code for token when TikTok redirects to frontend (e.g. https://ucontents.com/app/tiktok/profile).
     * Called by frontend after provider redirects with ?code=&state=
     */
    public function exchangeCode(Request $request, string $provider): JsonResponse
    {
        if ($provider !== 'tiktok') {
            return $this->error('Exchange code is only supported for TikTok', 400);
        }

        $socialAuthEnabled = $this->settingsService->get('features.social_auth.enabled', false);
        if (!$socialAuthEnabled) {
            return $this->error('Social authentication is disabled', 403);
        }

        $enabledProviders = $this->settingsService->get('features.social_auth.providers', []);
        if (!in_array($provider, $enabledProviders)) {
            return $this->error('TikTok authentication is not enabled', 403);
        }

        $config = $this->settingsService->get("features.social_auth.provider_configs.{$provider}", []);
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;
        $mode = $config['mode'] ?? null;

        if (!$clientId || !$clientSecret) {
            return $this->error('TikTok is not properly configured', 400);
        }

        $code = $request->input('code');
        $state = $request->input('state');
        $redirectUri = $request->input('redirect_uri');

        if (empty($code) || empty($state)) {
            return $this->error('Missing code or state', 400);
        }

        $callbackUrl = $redirectUri !== null && $redirectUri !== ''
            ? preg_replace('/[#?].*$/', '', $redirectUri)
            : $this->getSocialAuthCallbackUrl($provider);

        $request->merge(['code' => $code, 'state' => $state]);

        try {
            $driver = $this->getSocialiteDriver($provider, $clientId, $clientSecret, $callbackUrl, $mode)
                ->stateless();
            $socialiteUser = $driver->user();

            $result = $this->socialAuthAction->execute($provider, $socialiteUser);
            $token = $result['token'];
            $frontendUrl = $this->normalizeFrontendUrl(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')));
            $redirectUrl = $frontendUrl . "/auth/social/callback?token={$token}";

            return $this->success(['token' => $token, 'redirect_url' => $redirectUrl], 'OK');
        } catch (\Exception $e) {
            Log::error('Social auth exchange-code failed for TikTok: ' . $e->getMessage());
            return $this->error('Authentication failed. Please try again.', 400);
        }
    }

    /**
     * Redirect to frontend with error
     */
    private function redirectWithError(string $message): RedirectResponse
    {
        $frontendUrl = $this->normalizeFrontendUrl(config('app.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000'))));
        $error = urlencode($message);
        return redirect($frontendUrl . "/auth/login?error={$error}");
    }
}
