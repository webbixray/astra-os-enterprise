<?php

declare(strict_types=1);

namespace Tests\Feature\Cache;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

#[Group('feature')]
class CacheOperationsTest extends TestCase
{
    public function test_cache_store_configuration(): void
    {
        $stores = config('cache.stores');
        
        $this->assertArrayHasKey('redis', $stores);
        $this->assertEquals('redis', $stores['redis']['driver']);
        $this->assertArrayHasKey('connection', $stores['redis']);
    }

    public function test_cache_basic_operations(): void
    {
        Cache::flush();
        
        Cache::put('test-key', 'test-value', 60);
        
        $this->assertTrue(Cache::has('test-key'));
        $this->assertEquals('test-value', Cache::get('test-key'));
        
        Cache::forget('test-key');
        $this->assertFalse(Cache::has('test-key'));
    }

    public function test_cache_tags(): void
    {
        Cache::tags(['campaigns', 'analytics'])->put('campaign-1', ['impressions' => 1000], 60);
        
        $this->assertTrue(Cache::tags(['campaigns', 'analytics'])->has('campaign-1'));
        
        Cache::tags(['campaigns'])->flush();
        $this->assertFalse(Cache::tags(['campaigns', 'analytics'])->has('campaign-1'));
    }

    public function test_cache_increment_decrement(): void
    {
        Cache::put('counter', 0, 60);
        
        Cache::increment('counter');
        $this->assertEquals(1, Cache::get('counter'));
        
        Cache::increment('counter', 5);
        $this->assertEquals(6, Cache::get('counter'));
        
        Cache::decrement('counter', 2);
        $this->assertEquals(4, Cache::get('counter'));
    }

    public function test_redis_connection_healthy(): void
    {
        $this->assertTrue(Redis::connection()->ping());
    }

    public function test_cache_lock(): void
    {
        $lock = Cache::lock('critical-section', 10);
        
        $this->assertTrue($lock->get());
        
        $lock2 = Cache::lock('critical-section', 10);
        $this->assertFalse($lock2->get());
        
        $lock->release();
    }

    public function test_cache_remember(): void
    {
        Cache::flush();
        
        $value = Cache::remember('expensive-computation', 60, function () {
            return 'computed-value';
        });
        
        $this->assertEquals('computed-value', $value);
        $this->assertEquals('computed-value', Cache::get('expensive-computation'));
    }
}