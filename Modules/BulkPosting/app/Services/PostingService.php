<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services;

use Illuminate\Support\Facades\Log;
use Modules\BulkPosting\Models\BulkPostingContentItem;
use Modules\BulkPosting\Services\Posting\PostingAdapterFactory;
use Modules\BulkPosting\Services\Posting\PostResult;
use Modules\ProxySetup\Services\ProxyService;
use Modules\SocialConnection\Models\SocialConnectionChannel;

/**
 * Service for posting content to social media channels
 */
class PostingService
{
    public function __construct(
        private ProxyService $proxyService,
        private PostingAdapterFactory $adapterFactory
    ) {}

    /**
     * Post content to multiple channels
     *
     * @param BulkPostingContentItem $contentItem
     * @param SocialConnectionChannel[] $channels
     * @param array{caption: string, media_urls: string[], hashtags: string[]} $payload
     * @return array<int, array{success: bool, external_post_id?: string, error?: string, error_code?: string}>
     */
    public function postToChannels(BulkPostingContentItem $contentItem, array $channels, array $payload): array
    {
        $results = [];
        $userId = $contentItem->campaign?->user_id;

        foreach ($channels as $channel) {
            $result = $this->postToChannel($channel, $payload, $userId);
            $results[$channel->id] = $result->toArray();

            // Log the posting attempt
            Log::info('BulkPosting: Post attempt', [
                'content_item_id' => $contentItem->id,
                'channel_id' => $channel->id,
                'channel_type' => $channel->type,
                'provider' => $channel->provider,
                'success' => $result->success,
                'external_post_id' => $result->externalPostId,
                'error' => $result->error,
            ]);
        }

        return $results;
    }

    /**
     * Post content to a single channel
     *
     * @param SocialConnectionChannel $channel
     * @param array{caption: string, media_urls: string[], hashtags: string[]} $payload
     * @param int|null $userId
     * @return PostResult
     */
    public function postToChannel(SocialConnectionChannel $channel, array $payload, ?int $userId = null): PostResult
    {
        // Get the appropriate adapter for this channel
        $adapter = $this->adapterFactory->getAdapter($channel);

        if ($adapter === null) {
            return PostResult::failure(
                "No posting adapter available for {$channel->provider}/{$channel->type}",
                'UNSUPPORTED_PLATFORM'
            );
        }

        // Get proxy configuration
        $curlOpts = $this->getProxyConfig($channel, $userId);

        try {
            $result = $adapter->post($channel, $payload, $curlOpts);

            // If posting failed and we have a proxy, check if we should stop
            if (!$result->success && !empty($curlOpts) && $userId) {
                $shouldStop = $this->proxyService->shouldStopOnFailure($userId);
                if ($shouldStop) {
                    Log::warning('BulkPosting: Proxy failure - stopping automation', [
                        'channel_id' => $channel->id,
                        'user_id' => $userId,
                        'error' => $result->error,
                    ]);
                    return PostResult::failure(
                        'Posting stopped due to proxy failure: ' . ($result->error ?? 'Unknown error'),
                        'PROXY_FAILURE_STOP'
                    );
                }

                // Try again without proxy
                Log::info('BulkPosting: Retrying without proxy after failure', [
                    'channel_id' => $channel->id,
                    'original_error' => $result->error,
                ]);
                $result = $adapter->post($channel, $payload, []);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('BulkPosting: Exception during posting', [
                'channel_id' => $channel->id,
                'provider' => $channel->provider,
                'type' => $channel->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PostResult::failure($e->getMessage(), 'EXCEPTION');
        }
    }

    /**
     * Get proxy configuration for a channel
     *
     * @param SocialConnectionChannel $channel
     * @param int|null $userId
     * @return array
     */
    protected function getProxyConfig(SocialConnectionChannel $channel, ?int $userId): array
    {
        try {
            $proxy = $this->proxyService->getProxyForChannel($channel);

            if ($proxy === null) {
                return [];
            }

            return $this->proxyService->getProxyCurlConfig($proxy);
        } catch (\Throwable $e) {
            // ProxySetup tables may be missing or proxy config may fail
            Log::warning('BulkPosting: Failed to get proxy config', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check if a channel type is supported for posting
     *
     * @param string $provider
     * @param string $type
     * @return bool
     */
    public function isSupported(string $provider, string $type): bool
    {
        return $this->adapterFactory->getAdapterFor($provider, $type) !== null;
    }

    /**
     * Get all supported platforms
     *
     * @return array
     */
    public function getSupportedPlatforms(): array
    {
        return $this->adapterFactory->getSupportedPlatforms();
    }
}
