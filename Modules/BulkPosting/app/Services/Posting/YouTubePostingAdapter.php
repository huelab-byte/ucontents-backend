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
        $title = !empty($payload['youtube_heading']) ? $payload['youtube_heading'] : ($payload['caption'] ?? 'Untitled Video');
        $description = $this->buildDescription($payload);
        $tags = $this->extractTags($payload);

        // Step 1: Download the video content
        $videoContent = $this->downloadVideo($videoUrl, $curlOpts);
        if ($videoContent === null) {
            return PostResult::failure('Failed to download video from URL', 'DOWNLOAD_FAILED');
        }

        // Step 2: Initialize resumable upload
        $initResult = $this->initializeResumableUpload($accessToken, $title, $description, $tags, strlen($videoContent), $curlOpts);
        if ($initResult['uploadUrl'] === null) {
            return PostResult::failure(
                $initResult['error'] ?? 'Failed to initialize YouTube upload',
                $initResult['error_code'] ?? 'INIT_FAILED'
            );
        }
        $uploadUrl = $initResult['uploadUrl'];

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
        // Support local file paths for desktop app usage
        if (file_exists($url) && is_readable($url)) {
            return file_get_contents($url) ?: null;
        }

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
     * Initialize resumable upload session.
     *
     * @return array{uploadUrl: ?string, error?: string, error_code?: string}
     */
    protected function initializeResumableUpload(string $accessToken, string $title, string $description, array $tags, int $contentLength, array $curlOpts): array
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
                $location = $response->header('Location');
                if (! empty($location)) {
                    return ['uploadUrl' => $location];
                }
                Log::warning('YouTubePostingAdapter: Init success but no Location header');

                return [
                    'uploadUrl' => null,
                    'error' => 'YouTube did not return upload URL (missing Location header).',
                    'error_code' => 'INIT_FAILED',
                ];
            }

            $status = $response->status();
            $body = $response->json() ?? [];
            $errors = $body['error']['errors'][0] ?? [];
            $reason = $errors['reason'] ?? $errors['message'] ?? null;
            $message = $body['error']['message'] ?? (string) $response->body();
            $userMessage = $this->formatYouTubeInitError($status, is_string($reason) ? $reason : null, is_string($message) ? $message : (string) $response->body());

            Log::warning('YouTubePostingAdapter: Failed to initialize upload', [
                'status' => $status,
                'body' => $body,
                'user_message' => $userMessage,
            ]);

            return [
                'uploadUrl' => null,
                'error' => $userMessage,
                'error_code' => 'INIT_FAILED',
            ];
        } catch (\Throwable $e) {
            Log::error('YouTubePostingAdapter: Exception initializing upload', [
                'error' => $e->getMessage(),
            ]);

            return [
                'uploadUrl' => null,
                'error' => 'YouTube init error: ' . $e->getMessage(),
                'error_code' => 'INIT_FAILED',
            ];
        }
    }

    /**
     * Format a user-friendly message for YouTube init failure.
     */
    protected function formatYouTubeInitError(int $status, ?string $reason, string $message): string
    {
        if ($status === 401) {
            return 'YouTube access token expired or invalid. Reconnect your YouTube channel in Connection settings.';
        }
        if ($status === 403) {
            if ($reason === 'quotaExceeded' || str_contains((string) $message, 'quota')) {
                return 'YouTube API quota exceeded. Try again later or check your Google Cloud quota.';
            }
            if ($reason === 'accessNotConfigured' || str_contains((string) $message, 'YouTube Data API')) {
                return 'YouTube Data API v3 is not enabled for this project. Enable it in Google Cloud Console.';
            }

            return 'YouTube rejected the request (403). Reconnect your channel or check Google Cloud project settings.';
        }
        if ($status === 400) {
            return 'Invalid video metadata: ' . (strlen($message) > 120 ? substr($message, 0, 120) . '…' : $message);
        }

        return "YouTube API error ({$status}): " . (strlen($message) > 100 ? substr($message, 0, 100) . '…' : $message);
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

        if (!filter_var($url, FILTER_VALIDATE_URL) && file_exists($url)) {
            $path = $url;
        } else {
            $path = parse_url($url, PHP_URL_PATH) ?? '';
        }

        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

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
