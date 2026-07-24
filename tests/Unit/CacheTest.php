<?php

namespace Tests\Unit;

use App\Core\Cache;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    public function testCacheReturnsTheFirstComputedValueUntilItIsForgotten(): void
    {
        $key = 'test-cache-' . bin2hex(random_bytes(4));
        self::assertSame('first', Cache::remember($key, 30, fn () => 'first'));
        self::assertSame('first', Cache::remember($key, 30, fn () => 'second'));
        Cache::forget($key);
        self::assertSame('second', Cache::remember($key, 30, fn () => 'second'));
        Cache::forget($key);
    }
}
