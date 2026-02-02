<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Services;

use Modules\ProxySetup\Models\Proxy;

class ProxyTestService
{
    /**
     * Test proxy connectivity
     *
     * @param Proxy $proxy
     * @return array{success: bool, message: string, response_time_ms: int|null, ip: string|null}
     */
    public function test(Proxy $proxy): array
    {
        $testUrl = config('proxysetup.test.test_url', 'https://httpbin.org/ip');
        $timeout = config('proxysetup.test.timeout', 10);

        $ch = curl_init();

        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $testUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            // Set proxy type
            $proxyType = match ($proxy->type) {
                'socks4' => CURLPROXY_SOCKS4,
                'socks5' => CURLPROXY_SOCKS5,
                'https' => CURLPROXY_HTTPS,
                default => CURLPROXY_HTTP,
            };

            curl_setopt($ch, CURLOPT_PROXY, "{$proxy->host}:{$proxy->port}");
            curl_setopt($ch, CURLOPT_PROXYTYPE, $proxyType);

            // Set proxy authentication if provided
            if ($proxy->username && $proxy->password) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxy->username}:{$proxy->password}");
            }

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $endTime = microtime(true);

            $responseTimeMs = (int) round(($endTime - $startTime) * 1000);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                return [
                    'success' => false,
                    'message' => "Connection failed: {$error}",
                    'response_time_ms' => null,
                    'ip' => null,
                ];
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'message' => "HTTP error: {$httpCode}",
                    'response_time_ms' => $responseTimeMs,
                    'ip' => null,
                ];
            }

            // Try to parse the response to get IP
            $ip = null;
            if ($response) {
                $data = json_decode($response, true);
                $ip = $data['origin'] ?? $data['ip'] ?? null;
            }

            return [
                'success' => true,
                'message' => 'Connection successful',
                'response_time_ms' => $responseTimeMs,
                'ip' => $ip,
            ];
        } finally {
            curl_close($ch);
        }
    }
}
