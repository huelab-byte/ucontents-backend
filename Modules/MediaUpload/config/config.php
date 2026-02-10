<?php

return [
    'name' => 'MediaUpload',
    'queue_name' => env('MEDIA_UPLOAD_QUEUE', 'media_uploads'),
    'dispatcher' => [
        'queue_limit' => 100, // Max number of jobs in Redis queue before pausing dispatch
        'loop_sleep_ms' => 1000,
    ],
];
