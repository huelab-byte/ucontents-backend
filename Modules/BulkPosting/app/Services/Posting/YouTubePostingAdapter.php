<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services\Posting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\SocialConnection\Models\SocialConnectionChannel;

/**
 * Posting adapter for YouTube
 */
class YouTubePostingAdapter implements PostingAdapterInterface
{
    private const YOUTUBE_UPLOAD_URL = 'https://www.googleapis.com/upload/youtube/v3/videos';
    private const YOUTUBE_API_URL = 'https://www.googleapis.com/youtube/v3';

    public function getProvider(): string
    {
        return 'google';
    }

    public function supports(string $provider, string $type): bool
    {
        return $provider === 'google' && $type === 'youtube_channel';
    }

    public function post(SocialConnectionChannel $channel, array $payload, array $curlOpts = []): PostResult
    {
        $account = $channel->account;
        $accessToken = $account?->access_token;

        if (empty($accessToken)) {
            return PostResult::failure('Missing access token for YouTube', 'NO_TOKEN');
        }

        $mediaUrls = $payload['media_urls'] ?? [];

        if (empty($mediaUrls)) {
            return PostResult::failure('YouTube requires a video file', 'NO_MEDIA');
        }

        $videoUrl = $mediaUrls[0];

        if (!$this->isVideoUrl($videoUrl)) {
            return PostResult::failure('YouTube only supports video content', 'INVALID_MEDIA_TYPE');
        }

        try {
            return $this->uploadVideo($channel, $accessToken, $videoUrl, $payload, $curlOpts);
        } catch (\Throwable $e) {
            Log::error('YouTubePostingAdapter: Upload failed', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return PostResult::failure($e->getMessage(), 'EXCEPTION');
        }
    }

    /**
     * Upload video to YouTube using resumable upload
     */
    protected function uploadVideo(SocialConnectionChannel $channel, string $accessToken, string $videoUrl, array $payload, array $curlOpts): PostResult
    {
        $title = $payload['caption'] ?? 'Untitled Video';
        $description = $this->buildDescription($payload);
        $tags = $this->extractTags($payload);

        // Step 1: Download the video content
        $videoContent = $this->downloadVideo($videoUrl, $curlOpts);
        if ($videoContent === null) {
            return PostResult::failure('Failed to download video from URL', 'DOWNLOAD_FAILED');
        }

        // Step 2: Initialize resumable upload
        $uploadUrl = $this->initializeResumableUpload($accessToken, $title, $description, $tags, strlen($videoContent), $curlOpts);
        if ($uploadUrl === null) {
            return PostResult::failure('Failed to initialize YouTube upload', 'INIT_FAILED');
        }

        // Step 3: Upload the video content
        $videoId = $this->uploadVideoContent($uploadUrl, $videoContent, $curlOpts);
        if ($videoId === null) {
            return PostResult::failure('Failed to upload video to YouTube', 'UPLOAD_FAILED');
        }

        return PostResult::success($videoId, [
            'youtube_url' => "https://www.youtube.com/watch?v={$videoId}",
        ]);
    }

    /**
     * Download video from URL
     */
    protected function downloadVideo(string $url, array $curlOpts): ?string
    {
        try {
            $request = Http::timeout(300); // 5 minutes for large videos

            if (!empty($curlOpts)) {
                $request = $request->withOptions($this->convertCurlOptsToGuzzle($curlOpts));
            }

            $response = $request->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('YouTubePostingAdapter: Failed to download video', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('YouTubePostingAdapter: Exception downloading video', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Initialize resumable upload session
     */
    protected function initializeResumableUpload(string $accessToken, string $title, string $description, array $tags, int $contentLength, array $curlOpts): ?string
    {
        $metadata = [
            'snippet' => [
                'title' => substr($title, 0, 100), // YouTube title limit
                'description' => $description,
                'tags' => $tags,
                'categoryId' => '22', // People & Blogs
            ],
            'status' => [
                'privacyStatus' => 'public',
                'selfDeclaredMadeForKids' => false,
            ],
        ];

        $url = self::YOUTUBE_UPLOAD_URL . '?' . http_build_query([
            'uploadType' => 'resumable',
            'part' => 'snippet,status',
        ]);

        try {
            $request = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'X-Upload-Content-Length' => $contentLength,
                    'X-Upload-Content-Type' => 'video/*',
                ]);

            if (!empty($curlOpts)) {
                $request = $request->withOptions($this->convertCurlOptsToGuzzle($curlOpts));
            }

            $response = $request->post($url, $metadata);

            if ($response->successful()) {
                return $response->header('Location');
            }

            Log::warning('YouTubePostingAdapter: Failed to initialize upload', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('YouTubePostingAdapter: Exception initializing upload', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Upload video content to the resumable upload URL
     */
    protected function uploadVideoContent(string $uploadUrl, string $videoContent, array $curlOpts): ?string
    {
        try {
            $request = Http::timeout(600) // 10 minutes for upload
                ->withHeaders([
                    'Content-Type' => 'video/*',
                    'Content-Length' => strlen($videoContent),
                ])
                ->withBody($videoContent, 'video/*');

            if (!empty($curlOpts)) {
                $request = $request->withOptions($this->convertCurlOptsToGuzzle($curlOpts));
            }

            $response = $request->put($uploadUrl);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::warning('YouTubePostingAdapter: Failed to upload video content', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('YouTubePostingAdapter: Exception uploading video', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build description with hashtags
     */
    protected function buildDescription(array $payload): string
    {
        $caption = $payload['caption'] ?? '';
        $hashtags = $payload['hashtags'] ?? [];

        if (!empty($hashtags)) {
            $hashtagString = implode(' ', array_map(function ($tag) {
                return str_starts_with($tag, '#') ? $tag : "#{$tag}";
            }, $hashtags));

            if (!empty($caption)) {
                $caption .= "\n\n" . $hashtagString;
            } else {
                $caption = $hashtagString;
            }
        }

        return $caption;
    }

    /**
     * Extract tags from payload (without # prefix)
     */
    protected function extractTags(array $payload): array
    {
        $hashtags = $payload['hashtags'] ?? [];

        return array_map(function ($tag) {
            return ltrim($tag, '#');
        }, $hashtags);
    }

    /**
     * Check if URL points to a video file
     */
    protected function isVideoUrl(string $url): bool
    {
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', 'm4v', '3gp'];
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
