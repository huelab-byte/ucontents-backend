<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External AI Chat Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the external AI chat service.
    |
    */
    'base_url' => env('EXTERNAL_AI_CHAT_URL', 'https://gpt.ucontents.com'),
    'api_key' => env('EXTERNAL_AI_CHAT_API_KEY', 'pk_prod_tbQFGQFKIb8SeyvaPqAJX7nrXk7ZRlJU'),
    'timeout' => env('EXTERNAL_AI_CHAT_TIMEOUT', 120),
];
