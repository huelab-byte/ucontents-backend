<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | ProxySetup Module Configuration
    |--------------------------------------------------------------------------
    */
    
    'name' => 'ProxySetup',
    'enabled' => true,
    
    'features' => [
        'proxy_management' => [
            'enabled' => true,
            'admin_only' => false,
        ],
        'proxy_testing' => [
            'enabled' => true,
            'admin_only' => false,
        ],
    ],
    
    'endpoints' => [
        'customer' => [
            'manage_proxies' => ['enabled' => true],
            'test_proxy' => ['enabled' => true],
            'manage_settings' => ['enabled' => true],
        ],
    ],
    
    'permissions' => [
        'customer' => [
            'view_proxies',
            'manage_proxies',
        ],
    ],
    
    'proxy_types' => ['http', 'https', 'socks4', 'socks5'],
    
    'test' => [
        'timeout' => 10, // seconds
        'test_url' => 'https://httpbin.org/ip',
    ],
];
