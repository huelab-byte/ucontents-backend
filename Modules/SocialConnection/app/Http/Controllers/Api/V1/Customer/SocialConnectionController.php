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
            ->get(['provider', 'enabled', 'extra']);

        // Meta can be "enabled" via sub-features (facebook/instagram) even if provider.enabled is false.
        // Return only providers that are effectively enabled for customers.
        $apps = $apps
            ->filter(fn (SocialProviderApp $app) => $this->isProviderEnabledForCustomer($app))
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
        if (
            !$app ||
            !$this->isProviderEnabledForCustomer($app) ||
            empty($app->client_id) ||
            empty($app->client_secret)
        ) {
            return $this->error('Provider is not configured or enabled', 400);
        }

        $callbackUrl = rtrim(config('app.url', env('APP_URL', 'http://localhost:8000')), '/')
            . "/api/v1/customer/social-connection/{$provider}/callback";

        // For Meta provider, accept channel_types filter (facebook_page, facebook_profile, instagram_business)
        $validated = $request->validated();
        $channelTypes = ($provider === 'meta' && isset($validated['channel_types']))
            ? $validated['channel_types']
            : null;

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

        $callbackUrl = rtrim(config('app.url', env('APP_URL', 'http://localhost:8000')), '/')
            . "/api/v1/customer/social-connection/{$provider}/callback";

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

