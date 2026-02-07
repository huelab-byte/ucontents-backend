<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services\Posting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\SocialConnection\Models\SocialConnectionChannel;

/**
 * Posting adapter for Meta platforms (Facebook and Instagram)
 */
class MetaPostingAdapter implements PostingAdapterInterface
{
    private const GRAPH_API_VERSION = 'v19.0';
    private const GRAPH_API_BASE = 'https://graph.facebook.com';

    public function getProvider(): string
    {
        return 'meta';
    }

    public function supports(string $provider, string $type): bool
    {
        if ($provider !== 'meta') {
            return false;
        }

        return in_array($type, [
            'facebook_profile',
            'facebook_page',
            'instagram_business',
        ], true);
    }

    public function post(SocialConnectionChannel $channel, array $payload, array $curlOpts = []): PostResult
    {
        return match ($channel->type) {
            'facebook_page' => $this->postToFacebookPage($channel, $payload, $curlOpts),
            'facebook_profile' => $this->postToFacebookProfile($channel, $payload, $curlOpts),
            'instagram_business' => $this->postToInstagram($channel, $payload, $curlOpts),
            default => PostResult::failure("Unsupported channel type: {$channel->type}"),
        };
    }

    /**
     * Post to a Facebook Page
     */
    protected function postToFacebookPage(SocialConnectionChannel $channel, array $payload, array $curlOpts): PostResult
    {
        $tokenContext = $channel->token_context ?? [];
        $accessToken = $tokenContext['page_access_token'] ?? null;

        if (empty($accessToken)) {
            return PostResult::failure('Missing page access token', 'NO_TOKEN');
        }

        $pageId = $channel->provider_channel_id;
        $caption = $this->buildCaption($payload);
        $mediaUrls = $payload['media_urls'] ?? [];

        try {
            // If we have media, post with media
            if (!empty($mediaUrls)) {
                return $this->postWithMediaToFacebook($pageId, $accessToken, $caption, $mediaUrls, $curlOpts, $payload['media_items'] ?? []);
            }

            // Text-only post
            return $this->postTextToFacebook($pageId, $accessToken, $caption, $curlOpts);
        } catch (\Throwable $e) {
            Log::error('MetaPostingAdapter: Facebook page post failed', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);
            return PostResult::failure($e->getMessage(), 'EXCEPTION');
        }
    }

    /**
     * Post to a Facebook Profile
     */
    protected function postToFacebookProfile(SocialConnectionChannel $channel, array $payload, array $curlOpts): PostResult
    {
        $tokenContext = $channel->token_context ?? [];
        $accessToken = $tokenContext['user_access_token'] ?? null;

        if (empty($accessToken)) {
            // Try to get from account
            $account = $channel->account;
            $accessToken = $account?->access_token;
        }

        if (empty($accessToken)) {
            return PostResult::failure('Missing user access token', 'NO_TOKEN');
        }

        $caption = $this->buildCaption($payload);
        $mediaUrls = $payload['media_urls'] ?? [];

        try {
            if (!empty($mediaUrls)) {
                return $this->postWithMediaToFacebook('me', $accessToken, $caption, $mediaUrls, $curlOpts, $payload['media_items'] ?? []);
            }

            return $this->postTextToFacebook('me', $accessToken, $caption, $curlOpts);
        } catch (\Throwable $e) {
            Log::error('MetaPostingAdapter: Facebook profile post failed', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);
            return PostResult::failure($e->getMessage(), 'EXCEPTION');
        }
    }

    /**
     * Post to Instagram Business Account
     */
    protected function postToInstagram(SocialConnectionChannel $channel, array $payload, array $curlOpts): PostResult
    {
        $tokenContext = $channel->token_context ?? [];
        $accessToken = $tokenContext['user_access_token'] ?? null;

        if (empty($accessToken)) {
            $account = $channel->account;
            $accessToken = $account?->access_token;
        }

        if (empty($accessToken)) {
            return PostResult::failure('Missing access token for Instagram', 'NO_TOKEN');
        }

        $igUserId = $channel->provider_channel_id;
        $caption = $this->buildCaption($payload);
        $mediaUrls = $payload['media_urls'] ?? [];

        if (empty($mediaUrls)) {
            return PostResult::failure('Instagram requires at least one media item', 'NO_MEDIA');
        }

        try {
            // Instagram requires a two-step process: create container, then publish
            $mediaUrl = $mediaUrls[0]; // Instagram single post uses first media

            // Determine video status from media_items (preferred) or URL extension
            $isVideo = false;
            if (isset($payload['media_items'][0])) {
                $item = $payload['media_items'][0];
                $mediaUrl = $item['url'];
                $isVideo = $item['is_video'] ?? $this->isVideoUrl($mediaUrl);
            } else {
                $isVideo = $this->isVideoUrl($mediaUrl);
            }

            // Step 1: Create media container
            if ($this->isLocalUrl($mediaUrl)) {
                if (!$isVideo) {
                    return PostResult::failure('Instagram images must be hosted on a public URL. Localhost is not supported for images.', 'LOCAL_IMAGE_NOT_SUPPORTED');
                }

                // Use Resumable Upload for local videos
                $containerResult = $this->uploadVideoToInstagramResumable($igUserId, $accessToken, $mediaUrl, $caption, $curlOpts);
            } else {
                // Use standard public URL method
                $containerResult = $this->createInstagramContainer($igUserId, $accessToken, $mediaUrl, $caption, $isVideo, $curlOpts);
            }

            if (!$containerResult['success']) {
                return PostResult::failure($containerResult['error'] ?? 'Failed to create container', $containerResult['error_code'] ?? 'CONTAINER_FAILED');
            }

            $containerId = $containerResult['container_id'];

            // For video, wait for processing
            if ($isVideo) {
                $statusResult = $this->waitForInstagramContainerReady($containerId, $accessToken, $curlOpts);
                if (!$statusResult['success']) {
                    return PostResult::failure($statusResult['error'] ?? 'Video processing failed', 'VIDEO_PROCESSING_FAILED');
                }
            }

            // Step 2: Publish the container
            return $this->publishInstagramContainer($igUserId, $accessToken, $containerId, $curlOpts);
        } catch (\Throwable $e) {
            Log::error('MetaPostingAdapter: Instagram post failed', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);
            return PostResult::failure($e->getMessage(), 'EXCEPTION');
        }
    }

    /**
     * Post text-only to Facebook
     */
    protected function postTextToFacebook(string $targetId, string $accessToken, string $message, array $curlOpts): PostResult
    {
        $url = $this->buildGraphUrl("{$targetId}/feed");

        $response = $this->makeRequest('POST', $url, [
            'message' => $message,
            'access_token' => $accessToken,
        ], $curlOpts);

        if (!$response->successful()) {
            $error = $response->json('error.message', 'Unknown error');
            $errorCode = $response->json('error.code', 'UNKNOWN');
            return PostResult::failure($error, (string) $errorCode);
        }

        $postId = $response->json('id');
        if (empty($postId)) {
            return PostResult::failure('No post ID returned', 'NO_POST_ID');
        }

        return PostResult::success($postId);
    }

    /**
     * Post with media to Facebook (photo or video)
     */
    protected function postWithMediaToFacebook(string $targetId, string $accessToken, string $message, array $mediaUrls, array $curlOpts, array $mediaItems = []): PostResult
    {
        $mediaUrl = $mediaUrls[0]; // Use first media for now

        // Determine video status from media_items (preferred) or URL extension
        $isVideo = false;
        if (isset($mediaItems[0])) {
            $item = $mediaItems[0];
            $mediaUrl = $item['url'];
            $isVideo = $item['is_video'] ?? $this->isVideoUrl($mediaUrl);
        } else {
            $isVideo = $this->isVideoUrl($mediaUrl);
        }

        if ($isVideo) {
            return $this->postVideoToFacebook($targetId, $accessToken, $message, $mediaUrl, $curlOpts);
        }

        return $this->postPhotoToFacebook($targetId, $accessToken, $message, $mediaUrl, $curlOpts);
    }

    /**
     * Post a photo to Facebook
     */
    protected function postPhotoToFacebook(string $targetId, string $accessToken, string $message, string $photoUrl, array $curlOpts): PostResult
    {
        $url = $this->buildGraphUrl("{$targetId}/photos");

        // Check if URL is local - need to upload file directly
        if ($this->isLocalUrl($photoUrl)) {
            return $this->uploadPhotoDirectlyToFacebook($targetId, $accessToken, $message, $photoUrl);
        }

        $response = $this->makeRequest('POST', $url, [
            'url' => $photoUrl,
            'caption' => $message,
            'access_token' => $accessToken,
        ], $curlOpts);

        if (!$response->successful()) {
            $error = $response->json('error.message', 'Unknown error');
            $errorCode = $response->json('error.code', 'UNKNOWN');
            return PostResult::failure($error, (string) $errorCode);
        }

        $postId = $response->json('post_id') ?? $response->json('id');
        if (empty($postId)) {
            return PostResult::failure('No post ID returned', 'NO_POST_ID');
        }

        return PostResult::success($postId);
    }

    /**
     * Upload photo directly to Facebook (for local files)
     */
    protected function uploadPhotoDirectlyToFacebook(string $targetId, string $accessToken, string $message, string $photoUrl): PostResult
    {
        $localPath = $this->urlToLocalPath($photoUrl);

        if ($localPath === null || !file_exists($localPath)) {
            return PostResult::failure('Cannot access local photo file: ' . $photoUrl, 'LOCAL_FILE_NOT_FOUND');
        }

        $url = $this->buildGraphUrl("{$targetId}/photos");

        try {
            $response = Http::timeout(120)
                ->attach('source', file_get_contents($localPath), basename($localPath))
                ->post($url, [
                    'caption' => $message,
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                $error = $response->json('error.message', 'Unknown error');
                $errorCode = $response->json('error.code', 'UNKNOWN');
                return PostResult::failure($error, (string) $errorCode);
            }

            $postId = $response->json('post_id') ?? $response->json('id');
            if (empty($postId)) {
                return PostResult::failure('No post ID returned', 'NO_POST_ID');
            }

            return PostResult::success($postId);
        } catch (\Throwable $e) {
            return PostResult::failure('Photo upload failed: ' . $e->getMessage(), 'UPLOAD_EXCEPTION');
        }
    }

    /**
     * Post a video to Facebook
     */
    protected function postVideoToFacebook(string $targetId, string $accessToken, string $message, string $videoUrl, array $curlOpts): PostResult
    {
        $url = $this->buildGraphUrl("{$targetId}/videos");

        // Check if URL is local/localhost - need to upload file directly
        if ($this->isLocalUrl($videoUrl)) {
            return $this->uploadVideoDirectlyToFacebook($targetId, $accessToken, $message, $videoUrl, $curlOpts);
        }

        $response = $this->makeRequest('POST', $url, [
            'file_url' => $videoUrl,
            'description' => $message,
            'access_token' => $accessToken,
        ], $curlOpts);

        if (!$response->successful()) {
            $error = $response->json('error.message', 'Unknown error');
            $errorCode = $response->json('error.code', 'UNKNOWN');
            return PostResult::failure($error, (string) $errorCode);
        }

        $videoId = $response->json('id');
        if (empty($videoId)) {
            return PostResult::failure('No video ID returned', 'NO_VIDEO_ID');
        }

        return PostResult::success($videoId);
    }

    /**
     * Upload local video to Instagram via Resumable Upload protocol
     */
    protected function uploadVideoToInstagramResumable(string $igUserId, string $accessToken, string $videoUrl, string $caption, array $curlOpts): array
    {
        // 1. Convert URL to local path
        $localPath = $this->urlToLocalPath($videoUrl);
        if ($localPath === null || !file_exists($localPath)) {
            return [
                'success' => false,
                'error' => 'Cannot access local video file',
                'error_code' => 'LOCAL_FILE_NOT_FOUND',
            ];
        }

        // 2. Start Session
        // Note: Using v19.0 endpoint for resumable uploads
        $url = $this->buildGraphUrl("{$igUserId}/media");

        $params = [
            'upload_type' => 'resumable',
            'media_type' => 'REELS',
            'caption' => $caption,
            'access_token' => $accessToken,
        ];


        $response = $this->makeRequest('POST', $url, $params, $curlOpts);


        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('error.message', 'Failed to start upload session'),
                'error_code' => (string) $response->json('error.code', 'SESSION_START_FAILED'),
            ];
        }

        $uploadUri = $response->json('uri');
        $containerId = $response->json('id');

        if (empty($uploadUri) || empty($containerId)) {
            return [
                'success' => false,
                'error' => 'Invalid session response from Instagram',
                'error_code' => 'INVALID_SESSION_RESPONSE',
            ];
        }

        // 3. Upload File Content
        try {
            // We use Guzzle directly here to stream the file body with specific headers
            $client = new \GuzzleHttp\Client();

            // Prepare options from curlOpts (proxy etc)
            $options = $this->convertCurlOptsToGuzzle($curlOpts);

            $fileSize = filesize($localPath);
            $options['headers'] = [
                'Authorization' => 'OAuth ' . $accessToken,
                'offset' => '0',
                'file_size' => (string) $fileSize,
                'Content-Type' => 'application/octet-stream',
            ];

            $options['body'] = fopen($localPath, 'r');
            $options['timeout'] = 300; // 5 mins for upload

            $uploadResponse = $client->post($uploadUri, $options);

            if ($uploadResponse->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'error' => 'Upload failed with status ' . $uploadResponse->getStatusCode(),
                    'error_code' => 'UPLOAD_FAILED',
                ];
            }

            return [
                'success' => true,
                'container_id' => $containerId,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Upload exception: ' . $e->getMessage(),
                'error_code' => 'UPLOAD_EXCEPTION',
            ];
        }
    }

    /**
     * Create Instagram media container
     */
    protected function createInstagramContainer(string $igUserId, string $accessToken, string $mediaUrl, string $caption, bool $isVideo, array $curlOpts): array
    {
        $url = $this->buildGraphUrl("{$igUserId}/media");

        $params = [
            'caption' => $caption,
            'access_token' => $accessToken,
        ];

        if ($isVideo) {
            $params['media_type'] = 'REELS';
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        $response = $this->makeRequest('POST', $url, $params, $curlOpts);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('error.message', 'Unknown error'),
                'error_code' => (string) $response->json('error.code', 'UNKNOWN'),
            ];
        }

        $containerId = $response->json('id');
        if (empty($containerId)) {
            return [
                'success' => false,
                'error' => 'No container ID returned',
                'error_code' => 'NO_CONTAINER_ID',
            ];
        }

        return [
            'success' => true,
            'container_id' => $containerId,
        ];
    }

    /**
     * Wait for Instagram container to be ready (for video processing)
     */
    protected function waitForInstagramContainerReady(string $containerId, string $accessToken, array $curlOpts, int $maxAttempts = 30): array
    {
        $url = $this->buildGraphUrl($containerId);
        Log::info("[IG Bulk] Waiting for container {$containerId} to be ready...");

        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->makeRequest('GET', $url, [
                'fields' => 'status_code,status',
                'access_token' => $accessToken,
            ], $curlOpts);

            if (!$response->successful()) {
                $error = $response->json('error.message', 'Failed to check container status');
                Log::error("[IG Bulk] Status check failed for {$containerId}: {$error}");
                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            $status = $response->json('status_code');
            $statusRaw = $response->json('status'); // Sometimes localized or different?
            Log::info("[IG Bulk] Container {$containerId} status: {$status} (Attempt " . ($i + 1) . ")");

            if ($status === 'FINISHED') {
                return ['success' => true];
            }

            if ($status === 'ERROR') {
                Log::error("[IG Bulk] Container {$containerId} failed processing. Status: ERROR");
                return [
                    'success' => false,
                    'error' => 'Video processing failed (Instagram returned ERROR status)',
                ];
            }

            // Wait 5 seconds before checking again (increase from 2 to be safer)
            sleep(5);
        }

        Log::error("[IG Bulk] Container {$containerId} timed out processing.");
        return [
            'success' => false,
            'error' => 'Video processing timeout',
        ];
    }

    /**
     * Publish Instagram container
     */
    protected function publishInstagramContainer(string $igUserId, string $accessToken, string $containerId, array $curlOpts): PostResult
    {
        $url = $this->buildGraphUrl("{$igUserId}/media_publish");
        Log::info("[IG Bulk] Publishing container {$containerId}...");

        $response = $this->makeRequest('POST', $url, [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ], $curlOpts);

        if (!$response->successful()) {
            $error = $response->json('error.message', 'Unknown error');
            $errorCode = $response->json('error.code', 'UNKNOWN');
            $subCode = $response->json('error.error_subcode', 'None');

            Log::error("[IG Bulk] Publish failed for {$containerId}. Error: {$error} (Code: {$errorCode}, Sub: {$subCode})");
            // Log full body just in case
            Log::error("[IG Bulk] Full Response: " . $response->body());

            return PostResult::failure($error, (string) $errorCode);
        }

        $mediaId = $response->json('id');
        Log::info("[IG Bulk] Successfully published. Media ID: {$mediaId}");

        if (empty($mediaId)) {
            return PostResult::failure('No media ID returned', 'NO_MEDIA_ID');
        }

        return PostResult::success($mediaId);
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
                $caption .= "\n\n" . $hashtagString;
            } else {
                $caption = $hashtagString;
            }
        }

        return $caption;
    }

    /**
     * Build Graph API URL
     */
    protected function buildGraphUrl(string $endpoint): string
    {
        return self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION . '/' . ltrim($endpoint, '/');
    }

    /**
     * Check if URL points to a video file
     */
    protected function isVideoUrl(string $url): bool
    {
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', 'm4v'];

        if (!filter_var($url, FILTER_VALIDATE_URL) && file_exists($url)) {
            $path = $url;
        } else {
            $path = parse_url($url, PHP_URL_PATH) ?? '';
        }

        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return in_array($extension, $videoExtensions, true);
    }

    /**
     * Check if URL is a local/localhost URL that Facebook cannot access
     */
    protected function isLocalUrl(string $url): bool
    {
        // Check if valid local file first
        if (file_exists($url)) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $localHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];

        // Check for localhost or local IP
        if (in_array($host, $localHosts, true)) {
            return true;
        }

        // Check for local network IPs (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
        if (
            filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            && filter_var($host, FILTER_VALIDATE_IP) !== false
        ) {
            return true;
        }

        return false;
    }

    /**
     * Upload video directly to Facebook (for local files)
     */
    protected function uploadVideoDirectlyToFacebook(string $targetId, string $accessToken, string $message, string $videoUrl, array $curlOpts): PostResult
    {
        // Convert URL to local file path
        $localPath = $this->urlToLocalPath($videoUrl);

        if ($localPath === null || !file_exists($localPath)) {
            return PostResult::failure('Cannot access local video file: ' . $videoUrl, 'LOCAL_FILE_NOT_FOUND');
        }

        $url = $this->buildGraphUrl("{$targetId}/videos");

        try {
            $response = Http::timeout(300) // 5 minutes for video upload
                ->attach('source', file_get_contents($localPath), basename($localPath))
                ->post($url, [
                    'description' => $message,
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                $error = $response->json('error.message', 'Unknown error');
                $errorCode = $response->json('error.code', 'UNKNOWN');
                return PostResult::failure($error, (string) $errorCode);
            }

            $videoId = $response->json('id');
            if (empty($videoId)) {
                return PostResult::failure('No video ID returned', 'NO_VIDEO_ID');
            }

            return PostResult::success($videoId);
        } catch (\Throwable $e) {
            return PostResult::failure('Video upload failed: ' . $e->getMessage(), 'UPLOAD_EXCEPTION');
        }
    }

    /**
     * Convert a localhost URL to a local file path
     */
    protected function urlToLocalPath(string $url): ?string
    {
        if (file_exists($url)) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null) {
            return null;
        }

        // Remove /storage/ prefix and map to storage/app/public
        if (str_starts_with($path, '/storage/')) {
            $relativePath = substr($path, strlen('/storage/'));
            return storage_path('app/public/' . $relativePath);
        }

        return null;
    }

    /**
     * Make HTTP request with optional proxy configuration
     */
    protected function makeRequest(string $method, string $url, array $data, array $curlOpts): \Illuminate\Http\Client\Response
    {
        $request = Http::timeout(60);

        // Apply proxy configuration if provided
        if (!empty($curlOpts)) {
            $request = $request->withOptions($this->convertCurlOptsToGuzzle($curlOpts));
        }

        return match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->asForm()->post($url, $data),
            default => $request->send($method, $url, ['form_params' => $data]),
        };
    }

    /**
     * Convert cURL options to Guzzle options
     */
    protected function convertCurlOptsToGuzzle(array $curlOpts): array
    {
        $guzzleOpts = [];

        if (!empty($curlOpts['proxy'])) {
            $proxyUrl = $curlOpts['proxy'];

            // Add authentication if provided
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
