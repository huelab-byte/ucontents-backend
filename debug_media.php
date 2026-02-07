<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\MediaUpload\Models\MediaUpload;

$media = MediaUpload::where('social_caption', 'LIKE', '%vivid red dimension%')->first();

if (!$media) {
    echo "Media not found.\n";
    exit(1);
}

echo "Media ID: " . $media->id . "\n";
echo "Title: " . $media->title . "\n";

$storageFile = $media->storageFile;

if (!$storageFile) {
    echo "No associated StorageFile.\n";
    exit(1);
}

echo "StorageFile ID: " . $storageFile->id . "\n";
echo "Mime Type: " . $storageFile->mime_type . "\n";
echo "Path: " . $storageFile->path . "\n";
echo "Driver: " . $storageFile->driver . "\n";
echo "URL: " . $storageFile->url . "\n";
