<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\SocialConnection\Http\Requests\ListChannelsRequest;
use Modules\SocialConnection\Actions\BulkAssignGroupAction;
use Modules\SocialConnection\Actions\CreateGroupAction;
use Modules\SocialConnection\Actions\DeleteChannelAction;
use Modules\SocialConnection\Actions\DeleteGroupAction;
use Modules\SocialConnection\Actions\DisconnectChannelAction;
use Modules\SocialConnection\Actions\HandleChannelCallbackAction;
use Modules\SocialConnection\Actions\InitiateChannelConnectAction;
use Modules\SocialConnection\Actions\SaveSelectedChannelsAction;
use Modules\SocialConnection\Actions\UpdateChannelStatusAction;
use Modules\SocialConnection\Actions\UpdateGroupAction;
use Modules\SocialConnection\Http\Requests\BulkAssignGroupRequest;
use Modules\SocialConnection\Http\Requests\ConnectProviderRequest;
use Modules\SocialConnection\Http\Requests\GetAvailableChannelsRequest;
use Modules\SocialConnection\Http\Requests\ListGroupsRequest;
use Modules\SocialConnection\Http\Requests\SaveSelectedChannelsRequest;
use Modules\SocialConnection\Http\Requests\StoreSocialConnectionGroupRequest;
use Modules\SocialConnection\Http\Requests\UpdateChannelStatusRequest;
use Modules\SocialConnection\Http\Requests\UpdateSocialConnectionGroupRequest;
use Modules\SocialConnection\Http\Resources\SocialConnectionChannelResource;
use Modules\SocialConnection\Http\Resources\SocialConnectionGroupResource;
use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\SocialConnection\Models\SocialConnectionGroup;
use Modules\SocialConnection\Models\SocialProviderApp;

class SocialConnectionController extends BaseApiController
{
    private const ALLOWED_PROVIDERS = ['meta', 'google', 'tiktok'];

    public function providers(): JsonResponse
    {
        $this->authorize('viewAny', SocialConnectionChannel::class);

        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $apps = SocialProviderApp::query()
            ->whereIn('provider', self::ALLOWED_PROVIDERS)
            ->orderBy('provider')
            ->get();

        // Meta can be "enabled" via sub-features (facebook/instagram) even if provider.enabled is false.
        // Meta is also shown when configured (has credentials) so customers can connect Facebook/Instagram.
        // Other providers require enabled flag.
        $apps = $apps
            ->filter(function (SocialProviderApp $app) {
                if ($this->isProviderEnabledForCustomer($app)) {
                    return true;
                }
                if ($app->provider === 'meta' && !empty($app->client_id) && !empty($app->client_secret)) {
                    return true;
                }
                return false;
            })
            ->values()
            ->map(fn (SocialProviderApp $app) => [
                'provider' => $app->provider,
                'enabled' => true,
            ]);

        return $this->success($apps, 'Enabled providers retrieved successfully');
    }

    public function redirect(
        ConnectProviderRequest $request,
        string $provider,
        InitiateChannelConnectAction $action
    ): RedirectResponse|JsonResponse {
        $this->authorize('create', SocialConnectionChannel::class);

        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return $this->error('Invalid provider', 400);
        }

        $app = SocialProviderApp::query()->where('provider', $provider)->first();
        if (!$app) {
            return $this->error('Provider is not set up. An administrator can add it in Settings → Social Connection.', 400);
        }
        if (empty($app->client_id) || empty($app->client_secret)) {
            return $this->error(
                $provider === 'meta'
                    ? 'Meta App ID or App Secret is missing. Please complete the configuration in Admin → Settings → Social Connection.'
                    : 'Provider credentials are missing. Please complete the configuration in Admin → Settings → Social Connection.',
                400
            );
        }
        // Meta: allow redirect when app is configured (has credentials). Other providers require enabled flag.
        $allowed = $provider === 'meta'
            ? true
            : $this->isProviderEnabledForCustomer($app);
        if (!$allowed) {
            return $this->error(
                $provider === 'meta'
                    ? 'Facebook/Instagram connection is disabled. An administrator can enable it in Settings → Social Connection.'
                    : 'Provider is not enabled. An administrator can enable it in Settings → Social Connection.',
                400
            );
        }

        // For Meta provider, accept channel_types filter (facebook_page, facebook_profile, instagram_business)
        $validated = $request->validated();
        $channelTypes = ($provider === 'meta' && isset($validated['channel_types']))
            ? $validated['channel_types']
            : null;

        $callbackUrl = $this->buildOAuthCallbackUrl($provider, $channelTypes);

        $redirectUrl = $action->execute($request->user(), $provider, $app, $callbackUrl, $channelTypes);

        return $this->success(['redirect_url' => $redirectUrl], 'Redirect URL generated successfully');
    }

    public function callback(
        Request $request,
        string $provider,
        HandleChannelCallbackAction $action
    ): RedirectResponse|JsonResponse {
        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return $this->error('Invalid provider', 400);
        }

        $app = SocialProviderApp::query()->where('provider', $provider)->first();
        if (!$app || !$this->isProviderEnabledForCustomer($app)) {
            return $this->error('Provider is not enabled', 400);
        }

        $callbackUrl = $this->buildOAuthCallbackUrl($provider, null);

        try {
            $result = $action->execute(
                $provider,
                $app,
                $callbackUrl,
                $request->query('state')
            );

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000')));
            $baseUrl = rtrim($frontendUrl, '/');
            
            // For Meta, redirect with selection_token for channel selection
            if ($provider === 'meta' && isset($result['selection_token'])) {
                $redirectUrl = "{$baseUrl}/connection?provider={$provider}&status=select&token={$result['selection_token']}&channels_available={$result['channels_available']}";
                return redirect($redirectUrl);
            }

            // For other providers, redirect to success (immediate save)
            $channelsUpserted = $result['channels_upserted'] ?? 0;
            $redirectUrl = "{$baseUrl}/connection?provider={$provider}&status=success&channels_upserted={$channelsUpserted}";
            return redirect($redirectUrl);
        } catch (\Throwable $e) {
            Log::error('SocialConnection callback failed', [
                'provider' => $provider,
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000')));
            $baseUrl = rtrim($frontendUrl, '/');

            // Do not expose internal error details (SQL, stack traces, etc.) in the redirect URL.
            // Frontend can show a generic "Connection failed" message based on this status.
            $redirectUrl = "{$baseUrl}/connection?provider={$provider}&status=error&error=connection_failed";
            return redirect($redirectUrl);
        }
    }

    public function channels(ListChannelsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SocialConnectionChannel::class);

        if (!Schema::hasTable('social_connection_channels')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $channels = SocialConnectionChannel::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_active')
            ->orderBy('provider')
            ->orderBy('type')
            ->paginate($request->input('per_page', 20));

        return $this->paginatedResource($channels, SocialConnectionChannelResource::class, 'Channels retrieved successfully');
    }

    public function disconnect(
        SocialConnectionChannel $channel,
        DisconnectChannelAction $action
    ): JsonResponse {
        if (!Schema::hasTable('social_connection_channels')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $this->authorize('delete', $channel);

        $updated = $action->execute($channel);

        return $this->success(new SocialConnectionChannelResource($updated), 'Channel disconnected successfully');
    }

    public function destroy(
        SocialConnectionChannel $channel,
        DeleteChannelAction $action
    ): JsonResponse {
        if (!Schema::hasTable('social_connection_channels')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $this->authorize('delete', $channel);

        $action->execute($channel);

        return $this->success(null, 'Channel deleted successfully');
    }

    public function updateStatus(
        UpdateChannelStatusRequest $request,
        SocialConnectionChannel $channel,
        UpdateChannelStatusAction $action
    ): JsonResponse {
        if (!Schema::hasTable('social_connection_channels')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $this->authorize('update', $channel);

        $updated = $action->execute($channel, (bool) $request->validated()['is_active']);

        return $this->success(new SocialConnectionChannelResource($updated), 'Channel status updated successfully');
    }

    public function getAvailableChannels(
        GetAvailableChannelsRequest $request,
        string $provider,
        SaveSelectedChannelsAction $action
    ): JsonResponse {
        $this->authorize('create', SocialConnectionChannel::class);

        try {
            $channels = $action->getAvailableChannels(
                $request->user(),
                $provider,
                $request->validated()['token']
            );

            return $this->success($channels, 'Available channels retrieved successfully');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function saveSelectedChannels(
        SaveSelectedChannelsRequest $request,
        string $provider,
        SaveSelectedChannelsAction $action
    ): JsonResponse {
        $this->authorize('create', SocialConnectionChannel::class);

        try {
            $validated = $request->validated();
            $result = $action->execute(
                $request->user(),
                $provider,
                $validated['token'],
                $validated['selected_channels']
            );

            return $this->success([
                'channels_upserted' => $result['channels_upserted'],
            ], 'Selected channels saved successfully');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function indexGroups(ListGroupsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', SocialConnectionGroup::class);

        if (!Schema::hasTable('social_connection_groups')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $groups = SocialConnectionGroup::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return $this->success(SocialConnectionGroupResource::collection($groups), 'Groups retrieved successfully');
    }

    public function storeGroup(
        StoreSocialConnectionGroupRequest $request,
        CreateGroupAction $action
    ): JsonResponse {
        if (!Schema::hasTable('social_connection_groups')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $this->authorize('create', SocialConnectionGroup::class);

        $group = $action->execute($request->user(), $request->validated()['name']);

        return $this->success(new SocialConnectionGroupResource($group), 'Group created successfully', 201);
    }

    public function updateGroup(
        UpdateSocialConnectionGroupRequest $request,
        SocialConnectionGroup $group,
        UpdateGroupAction $action
    ): JsonResponse {
        if (!Schema::hasTable('social_connection_groups')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $this->authorize('update', $group);

        $updated = $action->execute($group, $request->validated()['name']);

        return $this->success(new SocialConnectionGroupResource($updated), 'Group updated successfully');
    }

    public function destroyGroup(
        SocialConnectionGroup $group,
        DeleteGroupAction $action
    ): JsonResponse {
        if (!Schema::hasTable('social_connection_groups')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $this->authorize('delete', $group);

        $action->execute($group);

        return $this->success(null, 'Group deleted successfully');
    }

    public function bulkAssignGroup(
        BulkAssignGroupRequest $request,
        BulkAssignGroupAction $action
    ): JsonResponse {
        $this->authorize('update', SocialConnectionChannel::class);

        if (!Schema::hasTable('social_connection_channels') || !Schema::hasTable('social_connection_groups')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        try {
            $validated = $request->validated();
            $updated = $action->execute(
                $request->user(),
                $validated['channel_ids'],
                $validated['group_id']
            );

            return $this->success([
                'updated' => $updated,
            ], 'Connections assigned to group successfully');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Exchange OAuth code for tokens when redirect_uri is the frontend (main domain).
     * Called by the frontend callback page after the provider redirects with ?code=&state=
     */
    public function exchangeCode(
        Request $request,
        string $provider,
        HandleChannelCallbackAction $action
    ): JsonResponse {
        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return $this->error('Invalid provider', 400);
        }

        $code = $request->input('code');
        $state = $request->input('state');
        if (empty($code) || empty($state)) {
            return $this->error('Missing code or state', 400);
        }

        if (!config('app.oauth_redirect_use_frontend', false)) {
            return $this->error('Exchange-code is only used when OAuth redirect is set to frontend', 400);
        }

        $app = SocialProviderApp::query()->where('provider', $provider)->first();
        if (!$app || !$this->isProviderEnabledForCustomer($app)) {
            return $this->error('Provider is not enabled', 400);
        }

        // Use redirect_uri from frontend so token exchange matches the exact URL the provider redirected to
        $redirectUri = $request->input('redirect_uri');
        $callbackUrl = $this->resolveCallbackUrlForExchange($provider, $redirectUri);

        // Make code/state available to Socialite (it reads from current request)
        $request->merge(['code' => $code, 'state' => $state]);

        try {
            $result = $action->execute(
                $provider,
                $app,
                $callbackUrl,
                $state
            );

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000')));
            $baseUrl = rtrim($frontendUrl, '/');

            if ($provider === 'meta' && isset($result['selection_token'])) {
                $redirectUrl = "{$baseUrl}/connection?provider={$provider}&status=select&token={$result['selection_token']}&channels_available={$result['channels_available']}";
                return $this->success(['redirect_url' => $redirectUrl], 'OK');
            }

            $channelsUpserted = $result['channels_upserted'] ?? 0;
            $redirectUrl = "{$baseUrl}/connection?provider={$provider}&status=success&channels_upserted={$channelsUpserted}";
            return $this->success(['redirect_url' => $redirectUrl], 'OK');
        } catch (\Throwable $e) {
            Log::error('SocialConnection exchangeCode failed', [
                'provider' => $provider,
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
            ]);

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000')));
            $baseUrl = rtrim($frontendUrl, '/');
            $redirectUrl = "{$baseUrl}/connection?provider={$provider}&status=error&error=connection_failed";
            return $this->success(['redirect_url' => $redirectUrl], 'OK');
        }
    }

    /**
     * Build OAuth callback URL: frontend /app/{platform}/{type} when OAUTH_REDIRECT_USE_FRONTEND is true, else backend.
     * Paths: /app/tiktok/profile, /app/youtube/channel, /app/facebook/profile, /app/facebook/page, /app/instagram/profile
     */
    private function buildOAuthCallbackUrl(string $provider, ?array $channelTypes = null): string
    {
        if (config('app.oauth_redirect_use_frontend', false)) {
            $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
            $path = $this->oauthCallbackPath($provider, $channelTypes);
            return $frontendUrl . '/app/' . $path;
        }

        return rtrim(config('app.url', env('APP_URL', 'http://localhost:8000')), '/')
            . "/api/v1/customer/social-connection/{$provider}/callback";
    }

    /**
     * Return path segment for frontend callback (no leading slash).
     * tiktok → tiktok/profile; google → youtube/channel; meta → facebook/profile|facebook/page|instagram/profile
     */
    private function oauthCallbackPath(string $provider, ?array $channelTypes = null): string
    {
        if ($provider === 'tiktok') {
            return 'tiktok/profile';
        }
        if ($provider === 'google') {
            return 'youtube/channel';
        }
        if ($provider === 'meta') {
            if (is_array($channelTypes) && count($channelTypes) === 1) {
                $t = $channelTypes[0];
                if ($t === 'facebook_profile') {
                    return 'facebook/profile';
                }
                if ($t === 'facebook_page') {
                    return 'facebook/page';
                }
                if ($t === 'instagram_business' || $t === 'instagram_profile') {
                    return 'instagram/profile';
                }
            }
            return 'facebook/profile';
        }
        return $provider . '/profile';
    }

    /**
     * Resolve callback URL for exchange-code: use redirect_uri from request if path is allowed, else build from provider.
     * Returns URL without query/fragment so it matches what was sent in the authorization request.
     */
    private function resolveCallbackUrlForExchange(string $provider, ?string $redirectUri): string
    {
        $allowedPaths = [
            'tiktok/profile',
            'youtube/channel',
            'facebook/profile',
            'facebook/page',
            'instagram/profile',
        ];
        if ($redirectUri !== null && $redirectUri !== '') {
            $parsed = parse_url($redirectUri);
            $path = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';
            foreach ($allowedPaths as $allowed) {
                if ($path === 'app/' . $allowed || $path === $allowed) {
                    return preg_replace('/[#?].*$/', '', $redirectUri);
                }
            }
        }
        return $this->buildOAuthCallbackUrl($provider, null);
    }

    private function isProviderEnabledForCustomer(SocialProviderApp $app): bool
    {
        if ($app->provider !== 'meta') {
            return (bool) $app->enabled;
        }

        // Meta provider is enabled when the provider itself is enabled OR when any sub-feature is enabled.
        // This matches the admin UI which toggles Facebook/Instagram sub-features independently.
        $extra = $app->extra ?? [];

        return (bool) $app->enabled
            || (bool) data_get($extra, 'facebook_page.enabled', false)
            || (bool) data_get($extra, 'facebook_profile.enabled', false)
            || (bool) data_get($extra, 'instagram_profile.enabled', false);
    }
}

