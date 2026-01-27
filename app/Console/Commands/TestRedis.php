<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TestRedis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Redis connectivity for cache and queue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Redis connections...');
        $this->newLine();

        // Test default Redis connection
        $this->info('1. Testing default Redis connection...');
        try {
            $result = Redis::connection('default')->ping();
            $this->info('   ✓ Default connection: SUCCESS');
            $this->line('   Response: ' . ($result === 'PONG' ? 'PONG' : $result));
        } catch (\Exception $e) {
            $this->error('   ✗ Default connection: FAILED');
            $this->error('   Error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test cache Redis connection
        $this->info('2. Testing cache Redis connection...');
        try {
            $result = Redis::connection('cache')->ping();
            $this->info('   ✓ Cache connection: SUCCESS');
            $this->line('   Response: ' . ($result === 'PONG' ? 'PONG' : $result));
        } catch (\Exception $e) {
            $this->error('   ✗ Cache connection: FAILED');
            $this->error('   Error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test cache store
        $this->info('3. Testing cache store (write/read)...');
        try {
            $testKey = 'redis_test_' . time();
            $testValue = 'test_value_' . uniqid();
            
            Cache::store('redis')->put($testKey, $testValue, 60);
            $retrieved = Cache::store('redis')->get($testKey);
            
            if ($retrieved === $testValue) {
                $this->info('   ✓ Cache store: SUCCESS');
                $this->line('   Written and retrieved value correctly');
                Cache::store('redis')->forget($testKey);
            } else {
                $this->error('   ✗ Cache store: FAILED');
                $this->error('   Value mismatch');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Cache store: FAILED');
            $this->error('   Error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test queue connection
        $this->info('4. Testing queue Redis connection...');
        try {
            $queueConnection = config('queue.connections.redis.connection', 'default');
            $result = Redis::connection($queueConnection)->ping();
            $this->info('   ✓ Queue connection: SUCCESS');
            $this->line('   Using connection: ' . $queueConnection);
            $this->line('   Response: ' . ($result === 'PONG' ? 'PONG' : $result));
        } catch (\Exception $e) {
            $this->error('   ✗ Queue connection: FAILED');
            $this->error('   Error: ' . $e->getMessage());
        }
        $this->newLine();

        // Display Redis configuration
        $this->info('5. Redis Configuration:');
        $this->line('   Host: ' . config('database.redis.default.host'));
        $this->line('   Port: ' . config('database.redis.default.port'));
        $this->line('   Database (default): ' . config('database.redis.default.database'));
        $this->line('   Database (cache): ' . config('database.redis.cache.database'));
        $this->line('   Client: ' . config('database.redis.client'));
        $this->line('   Cache Store: ' . config('cache.default'));
        $this->line('   Queue Connection: ' . config('queue.default'));

        return Command::SUCCESS;
    }
}
