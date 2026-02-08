<?php
$logFile = 'storage/logs/laravel.log';
if (!file_exists($logFile)) {
    echo "Log file not found.\n";
    exit;
}

$f = fopen($logFile, 'r');
fseek($f, -50000, SEEK_END); // larger seek
$content = fread($f, 50000);
fclose($f);

// Find ANY exception related to Database or ProcessMediaUploadJob
$patterns = [
    '/PDOException.*?\n(.*?)\n/s',
    '/SQLSTATE\[.*?\]: (.*?)\n/',
    '/ProcessMediaUploadJob failed.*?\n(.*?)\n/s'
];

foreach ($patterns as $pattern) {
    if (preg_match_all($pattern, $content, $matches)) {
        foreach ($matches[0] as $match) {
            echo "--- Match ---\n" . substr($match, 0, 500) . "\n\n";
        }
    }
}
