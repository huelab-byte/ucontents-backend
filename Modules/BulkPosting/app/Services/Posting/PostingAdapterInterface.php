<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services\Posting;

use Modules\SocialConnection\Models\SocialConnectionChannel;

/**
 * Interface for platform-specific posting adapters
 */
interface PostingAdapterInterface
{
    /**
     * Post content to a social media channel
     *
     * @param SocialConnectionChannel $channel The channel to post to
     * @param array{caption: string, media_urls: string[], hashtags: string[]} $payload Content payload
     * @param array $curlOpts Proxy configuration for cURL
     * @return PostResult Result of the posting operation
     */
    public function post(SocialConnectionChannel $channel, array $payload, array $curlOpts = []): PostResult;

    /**
     * Check if this adapter supports the given provider and channel type
     *
     * @param string $provider Provider name (e.g., 'meta', 'google', 'tiktok')
     * @param string $type Channel type (e.g., 'facebook_page', 'youtube_channel')
     * @return bool
     */
    public function supports(string $provider, string $type): bool;

    /**
     * Get the provider name this adapter handles
     *
     * @return string
     */
    public function getProvider(): string;
}
