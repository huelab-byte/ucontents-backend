<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Services\Providers\Adapters;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Modules\SocialConnection\Models\SocialProviderApp;
use Modules\SocialConnection\Services\Providers\ProviderAdapterInterface;
use Modules\UserManagement\Models\User;

class MetaAdapter implements ProviderAdapterInterface
{
    public function provider(): string
    {
        return 'meta';
    }

    public function makeAuthorizationRedirect(User $user, SocialProviderApp $app, string $callbackUrl, string $state): RedirectResponse
    {
        config(['services.facebook' => [
            'client_id' => $app->client_id,
            'client_secret' => $app->client_secret,
            'redirect' => $callbackUrl,
        ]]);

        $scopes = $app->scopes ?: [
            // Core permissions
            'public_profile',
            'email',
            // Page permissions
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_posts',      // Required for posting to pages
            'pages_read_user_content',
            // Instagram permissions
            'instagram_basic',
            'instagram_manage_insights',
            'instagram_content_publish', // Required for posting to Instagram
            // Business management
            'business_management',
            // Note: These permissions require Meta App Review for production use
        ];

        return Socialite::driver('facebook')
            ->redirectUrl($callbackUrl)
            ->stateless()
            ->scopes($scopes)
            ->with(['state' => $state])
            ->redirect();
    }

    public function handleCallback(User $user, SocialProviderApp $app, string $callbackUrl, ?array $channelTypes = null): array
    {
        config(['services.facebook' => [
            'client_id' => $app->client_id,
            'client_secret' => $app->client_secret,
            'redirect' => $callbackUrl,
        ]]);

        try {
            $socialiteUser = Socialite::driver('facebook')
                ->redirectUrl($callbackUrl)
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            Log::error('MetaAdapter: Socialite callback failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Failed to authenticate with Facebook: ' . $e->getMessage(), 0, $e);
        }

        $accessToken = $socialiteUser->token;
        if (empty($accessToken)) {
            Log::error('MetaAdapter: No access token received from Facebook', [
                'user_id' => $user->id,
            ]);
            throw new \RuntimeException('No access token received from Facebook');
        }

        $raw = $socialiteUser->getRaw() ?? [];

        // Fetch ALL Facebook pages the user manages (handle pagination),
        // and also include instagram_business_account references (sentosh-smm approach).
        $pages = [];
        try {
            $nextUrl = 'https://graph.facebook.com/v19.0/me/accounts';
            $params = [
                'fields' => 'id,name,username,link,category,picture{url},access_token,instagram_business_account',
                'limit' => 100,
            ];

            while ($nextUrl) {
                $resp = Http::withToken($accessToken)->get($nextUrl, $params);

                if (!$resp->successful()) {
                    Log::warning('MetaAdapter: failed to fetch pages (pagination)', [
                        'status' => $resp->status(),
                        'body' => $resp->body(),
                    ]);
                    break;
                }

                $data = $resp->json('data', []) ?? [];
                if (is_array($data)) {
                    $pages = array_merge($pages, $data);
                }

                $nextUrl = $resp->json('paging.next');
                // After first request, paging.next already has query params
                $params = [];
            }
        } catch (\Throwable $e) {
            Log::error('MetaAdapter: exception fetching pages', ['message' => $e->getMessage()]);
        }

        $channels = [];
        
        // Determine which channel types to include based on filter
        $includeFacebookProfile = $channelTypes === null || in_array('facebook_profile', $channelTypes, true);
        $includeFacebookPages = $channelTypes === null || in_array('facebook_page', $channelTypes, true);
        $includeInstagram = $channelTypes === null || in_array('instagram_business', $channelTypes, true);

        // Add Facebook profile channel if requested
        if ($includeFacebookProfile) {
            $channels[] = [
                'provider' => 'meta',
                'type' => 'facebook_profile',
                'provider_channel_id' => (string) $socialiteUser->getId(),
                'name' => (string) ($socialiteUser->getName() ?? 'Facebook Profile'),
                'username' => null,
                'avatar_url' => $socialiteUser->getAvatar(),
                'metadata' => [
                    'user_id' => $socialiteUser->getId(),
                ],
                'token_context' => [
                    'user_access_token' => $accessToken,
                ],
            ];
        }

        // Build channels for pages and collect IG business ids (if Instagram is requested)
        $igBusinessIds = [];
        if ($includeFacebookPages) {
            foreach ($pages as $page) {
                if (empty($page['id'])) {
                    continue;
                }
                
                $channels[] = [
                    'provider' => 'meta',
                    'type' => 'facebook_page',
                    'provider_channel_id' => (string) $page['id'],
                    'name' => (string) ($page['name'] ?? 'Facebook Page'),
                    'username' => $page['username'] ?? null,
                    'avatar_url' => $page['picture']['data']['url'] ?? null,
                    'metadata' => [
                        'page_id' => $page['id'],
                        'link' => $page['link'] ?? null,
                        'category' => $page['category'] ?? null,
                    ],
                    'token_context' => [
                        'page_access_token' => $page['access_token'] ?? null,
                    ],
                ];
                
                // Collect IG business IDs if Instagram is requested
                if ($includeInstagram && !empty($page['instagram_business_account']['id'])) {
                    $igBusinessIds[] = (string) $page['instagram_business_account']['id'];
                }
            }
        } elseif ($includeInstagram) {
            // If only Instagram requested, still need to fetch pages to get IG business accounts
            foreach ($pages as $page) {
                if (!empty($page['instagram_business_account']['id'])) {
                    $igBusinessIds[] = (string) $page['instagram_business_account']['id'];
                }
            }
        }

        // Fetch IG business profiles details by ids
        $igBusinessIds = array_values(array_unique(array_filter($igBusinessIds)));
        if ($includeInstagram && !empty($igBusinessIds)) {
            foreach (array_chunk($igBusinessIds, 50) as $chunk) {
                try {
                    $idsParam = implode(',', $chunk);
                    $igResp = Http::withToken($accessToken)->get('https://graph.facebook.com/v19.0/', [
                        'ids' => $idsParam,
                        'fields' => 'id,name,username,profile_picture_url',
                    ]);

                    if (!$igResp->successful()) {
                        Log::warning('MetaAdapter: failed to fetch IG profiles by ids', [
                            'status' => $igResp->status(),
                            'body' => $igResp->body(),
                        ]);
                        continue;
                    }

                    $igMap = $igResp->json() ?? [];
                    if (!is_array($igMap)) {
                        continue;
                    }

                    foreach ($igMap as $ig) {
                        if (!is_array($ig) || empty($ig['id'])) {
                            continue;
                        }
                        $channels[] = [
                            'provider' => 'meta',
                            'type' => 'instagram_business',
                            'provider_channel_id' => (string) $ig['id'],
                            'name' => (string) ($ig['name'] ?? $ig['username'] ?? 'Instagram'),
                            'username' => $ig['username'] ?? null,
                            'avatar_url' => $ig['profile_picture_url'] ?? null,
                            'metadata' => [
                                'ig_id' => $ig['id'],
                            ],
                            'token_context' => [
                                'user_access_token' => $accessToken,
                            ],
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('MetaAdapter: exception fetching IG profiles by ids', [
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [
            'identity' => [
                'provider_account_id' => (string) $socialiteUser->getId(),
                'email' => $socialiteUser->getEmail(),
                'display_name' => $socialiteUser->getName(),
                'avatar_url' => $socialiteUser->getAvatar(),
                'raw' => $raw,
            ],
            'tokens' => [
                'access_token' => $accessToken,
                'refresh_token' => $socialiteUser->refreshToken ?? null,
                'expires_at' => null,
                'scopes' => $app->scopes ?? [],
            ],
            'channels' => $channels,
        ];
    }
}

