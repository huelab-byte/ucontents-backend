<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services\Posting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\SocialConnection\Models\SocialConnectionChannel;

/**
 * Posting adapter for TikTok
 * Uses TikTok Content Posting API
 * @see https://developers.tiktok.com/doc/content-posting-api-get-started
 */
class TikTokPostingAdapter implements PostingAdapterInterface
{
    private const TIKTOK_API_BASE = 'https://open.tiktokapis.com/v2';

    public function getProvider(): string
    {
        return 'tiktok';
    }

    public function supports(string $provider, string $type): bool
    {
        return $provider === 'tiktok' && $type === 'tiktok_profile';
    }

    public function post(SocialConnectionChannel $channel, array $payload, array $curlOpts = []): PostResult
    {
        $account = $channel->account;
        $accessToken = $account?->access_token;

        if (empty($accessToken)) {
            return PostResult::failure('Missing access token for TikTok', 'NO_TOKEN');
        }

        $mediaUrls = $payload['media_urls'] ?? [];

        if (empty($mediaUrls)) {
            return PostResult::failure('TikTok requires a video file', 'NO_MEDIA');
        }

        $videoUrl = $mediaUrls[0];

        if (!$this->isVideoUrl($videoUrl)) {
            return PostResult::failure('TikTok only supports video content', 'INVALID_MEDIA_TYPE');
        }

        try {
            return $this->uploadVideo($accessToken, $videoUrl, $payload, $curlOpts);
        } catch (\Throwable $e) {
            Log::error('TikTokPostingAdapter: Upload failed', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return PostResult::failure($e->getMessage(), 'EXCEPTION');
        }
    }

    /**
     * Upload video to TikTok using the Content Posting API
     */
    protected function uploadVideo(string $accessToken, string $videoUrl, array $payload, array $curlOpts): PostResult
    {
        $caption = $this->buildCaption($payload);

        // Step 1: Initialize video upload (pull from URL)
        $initResult = $this->initializeUploadFromUrl($accessToken, $videoUrl, $caption, $curlOpts);

        if (!$initResult['success']) {
            return PostResult::failure($initResult['error'] ?? 'Failed to initialize TikTok upload', $initResult['error_code'] ?? 'INIT_FAILED');
        }

        $publishId = $initResult['publish_id'];

        // Step 2: Check publish status (TikTok processes the video asynchronously)
        $statusResult = $this->waitForPublishComplete($accessToken, $publishId, $curlOpts);

        if (!$statusResult['success']) {
            return PostResult::failure($statusResult['error'] ?? 'TikTok video processing failed', $statusResult['error_code'] ?? 'PROCESSING_FAILED');
        }

        return PostResult::success($publishId, [
            'status' => $statusResult['status'] ?? 'published',
        ]);
    }

    /**
     * Initialize video upload from URL (pull method)
     */
    protected function initializeUploadFromUrl(string $accessToken, string $videoUrl, string $caption, array $curlOpts): array
    {
        $url = self::TIKTOK_API_BASE . '/post/publish/video/init/';

        $postInfo = [
            'title' => substr($caption, 0, 150), // TikTok title limit
            'privacy_level' => 'PUBLIC_TO_EVERYONE',
            'disable_duet' => false,
            'disable_comment' => false,
            'disable_stitch' => false,
        ];

        $sourceInfo = [
            'source' => 'PULL_FROM_URL',
            'video_url' => $videoUrl,
        ];

        try {
            $request = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json; charset=UTF-8',
                ]);

            if (!empty($curlOpts)) {
                $request = $request->withOptions($this->convertCurlOptsToGuzzle($curlOpts));
            }

            $response = $request->post($url, [
                'post_info' => $postInfo,
                'source_info' => $sourceInfo,
            ]);

            $data = $response->json('data', []);
            $error = $response->json('error', []);

            if (!empty($error['code']) && $error['code'] !== 'ok') {
                return [
                    'success' => false,
                    'error' => $error['message'] ?? 'TikTok API error',
                    'error_code' => $error['code'] ?? 'API_ERROR',
                ];
            }

            if (empty($data['publish_id'])) {
                return [
                    'success' => false,
                    'error' => 'No publish ID returned',
                    'error_code' => 'NO_PUBLISH_ID',
                ];
            }

            return [
                'success' => true,
                'publish_id' => $data['publish_id'],
            ];
        } catch (\Throwable $e) {
            Log::error('TikTokPostingAdapter: Exception initializing upload', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Wait for TikTok to finish processing the video
     */
    protected function waitForPublishComplete(string $accessToken, string $publishId, array $curlOpts, int $maxAttempts = 30): array
    {
        $url = self::TIKTOK_API_BASE . '/post/publish/status/fetch/';

        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                $request = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type' => 'application/json; charset=UTF-8',
                    ]);

                if (!empty($curlOpts)) {
                    $request = $request->withOptions($this->convertCurlOptsToGuzzle($curlOpts));
                }

                $response = $request->post($url, [
                    'publish_id' => $publishId,
                ]);

                $data = $response->json('data', []);
                $error = $response->json('error', []);

                if (!empty($error['code']) && $error['code'] !== 'ok') {
                    return [
                        'success' => false,
                        'error' => $error['message'] ?? 'Status check failed',
                        'error_code' => $error['code'] ?? 'STATUS_ERROR',
                    ];
                }

                $status = $data['status'] ?? '';

                // TikTok publish statuses: PROCESSING_UPLOAD, PROCESSING_DOWNLOAD, SEND_TO_USER_INBOX, PUBLISH_COMPLETE, FAILED
                if ($status === 'PUBLISH_COMPLETE') {
                    return [
                        'success' => true,
                        'status' => 'published',
                    ];
                }

                if ($status === 'FAILED') {
                    $failReason = $data['fail_reason'] ?? 'Unknown failure';
                    return [
                        'success' => false,
                        'error' => "TikTok publish failed: {$failReason}",
                        'error_code' => 'PUBLISH_FAILED',
                    ];
                }

                // Still processing, wait and retry
                sleep(3);
            } catch (\Throwable $e) {
                Log::warning('TikTokPostingAdapter: Exception checking status', [
                    'publish_id' => $publishId,
                    'attempt' => $i + 1,
                    'error' => $e->getMessage(),
                ]);
                sleep(3);
            }
        }

        return [
            'success' => false,
            'error' => 'Timeout waiting for TikTok to process video',
            'error_code' => 'TIMEOUT',
        ];
    }

    /**
     * Build caption with hashtags
     */
    protected function buildCaption(array $payload): string
    {
        $caption = $payload['caption'] ?? '';
        $hashtags = $payload['hashtags'] ?? [];

        if (!empty($hashtags)) {
            $hashtagString = implode(' ', array_map(function ($tag) {
                return str_starts_with($tag, '#') ? $tag : "#{$tag}";
            }, $hashtags));

            if (!empty($caption)) {
                $caption .= ' ' . $hashtagString;
            } else {
                $caption = $hashtagString;
            }
        }

        return $caption;
    }

    /**
     * Check if URL points to a video file
     */
    protected function isVideoUrl(string $url): bool
    {
        $videoExtensions = ['mp4', 'mov', 'webm'];
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, $videoExtensions, true);
    }

    /**
     * Convert cURL options to Guzzle options
     */
    protected function convertCurlOptsToGuzzle(array $curlOpts): array
    {
        $guzzleOpts = [];

        if (!empty($curlOpts['proxy'])) {
            $proxyUrl = $curlOpts['proxy'];

            if (!empty($curlOpts['proxy_auth'])) {
                $parts = explode(':', $proxyUrl, 2);
                $host = $parts[0];
                $port = $parts[1] ?? '80';
                $proxyUrl = "http://{$curlOpts['proxy_auth']}@{$host}:{$port}";
            }

            $guzzleOpts['proxy'] = $proxyUrl;
        }

        return $guzzleOpts;
    }
}
