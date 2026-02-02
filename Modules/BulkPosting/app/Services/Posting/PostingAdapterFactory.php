<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services\Posting;

use Modules\SocialConnection\Models\SocialConnectionChannel;

/**
 * Factory for creating posting adapters based on channel provider/type
 */
class PostingAdapterFactory
{
    /** @var PostingAdapterInterface[] */
    private array $adapters;

    public function __construct(
        MetaPostingAdapter $metaAdapter,
        YouTubePostingAdapter $youtubeAdapter,
        TikTokPostingAdapter $tiktokAdapter
    ) {
        $this->adapters = [
            $metaAdapter,
            $youtubeAdapter,
            $tiktokAdapter,
        ];
    }

    /**
     * Get the appropriate adapter for a channel
     *
     * @param SocialConnectionChannel $channel
     * @return PostingAdapterInterface|null
     */
    public function getAdapter(SocialConnectionChannel $channel): ?PostingAdapterInterface
    {
        return $this->getAdapterFor($channel->provider, $channel->type);
    }

    /**
     * Get adapter for a specific provider and type
     *
     * @param string $provider
     * @param string $type
     * @return PostingAdapterInterface|null
     */
    public function getAdapterFor(string $provider, string $type): ?PostingAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($provider, $type)) {
                return $adapter;
            }
        }

        return null;
    }

    /**
     * Check if posting is supported for a channel
     *
     * @param SocialConnectionChannel $channel
     * @return bool
     */
    public function isSupported(SocialConnectionChannel $channel): bool
    {
        return $this->getAdapter($channel) !== null;
    }

    /**
     * Get all supported provider/type combinations
     *
     * @return array<array{provider: string, types: string[]}>
     */
    public function getSupportedPlatforms(): array
    {
        return [
            [
                'provider' => 'meta',
                'types' => ['facebook_profile', 'facebook_page', 'instagram_business'],
            ],
            [
                'provider' => 'google',
                'types' => ['youtube_channel'],
            ],
            [
                'provider' => 'tiktok',
                'types' => ['tiktok_profile'],
            ],
        ];
    }
}
