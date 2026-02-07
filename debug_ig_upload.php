<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\BulkPosting\Services\Posting\MetaPostingAdapter;
use Illuminate\Support\Facades\Log;

// 1. Find a connected Instagram Business channel
$channel = SocialConnectionChannel::where('type', 'instagram_business')
    ->where('is_active', true)
    ->first();

if (!$channel) {
    echo "No active Instagram Business channel found.\n";
    exit(1);
}

echo "Found Channel: " . $channel->name . " (ID: " . $channel->id . ")\n";
echo "Provider ID: " . $channel->provider_channel_id . "\n";

// 2. Resolve Access Token
$tokenContext = $channel->token_context ?? [];
$accessToken = $tokenContext['user_access_token'] ?? $channel->account?->access_token;

if (!$accessToken) {
    echo "No access token found.\n";
    exit(1);
}

echo "Access Token: " . substr($accessToken, 0, 10) . "...\n";

// 3. Find a video to upload
// We'll use the one from the previous debug: media ID 59
$mediaId = 59;
$media = \Modules\MediaUpload\Models\MediaUpload::find($mediaId);

if (!$media || !$media->storageFile) {
    echo "Test media not found.\n";
    exit(1);
}

$file = $media->storageFile;
$file->url = 'http://localhost:8000/storage/' . $file->path; // Force localhost URL for testing
echo "Test Video URL: " . $file->url . "\n";
echo "Test Video Path: " . $file->getLocalPath() . "\n";

if (!file_exists($file->getLocalPath())) {
    echo "Local file does not exist at " . $file->getLocalPath() . "\n";
    exit(1);
}

// 4. Instantiate Adapter and Test Upload
$adapter = new class extends MetaPostingAdapter {
    // Custom makeRequest to see raw error
    protected function makeRequest(string $method, string $url, array $data, array $curlOpts): \Illuminate\Http\Client\Response
    {
        echo "\n[DEBUG] Request $method $url\n";
        print_r($data);

        $response = parent::makeRequest($method, $url, $data, $curlOpts);

        echo "[DEBUG] Response Status: " . $response->status() . "\n";
        echo "[DEBUG] Response Body: " . $response->body() . "\n";

        return $response;
    }

    // Expose protected method
    public function testUpload($igUserId, $token, $url, $caption)
    {
        return $this->uploadVideoToInstagramResumable($igUserId, $token, $url, $caption, []);
    }
};

echo "Starting Resumable Upload Test...\n";

try {
    $result = $adapter->testUpload(
        $channel->provider_channel_id,
        $accessToken,
        $file->url,
        "Debug Caption"
    );

    print_r($result);

} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
