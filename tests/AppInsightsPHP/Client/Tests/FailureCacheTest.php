<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client\Tests;

use AppInsightsPHP\Client\FailureCache;
use ApplicationInsights\Channel\Contracts\Envelope;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class FailureCacheTest extends TestCase
{
    public function test_adding_to_failure_cache_when_cache_is_empty()
    {
        $cacheMock = $this->createMock(CacheInterface::class);

        $cacheMock->method('has')->willReturn(false);
        $cacheMock->expects($this->once())
            ->method('set')
            ->with(FailureCache::CACHE_CHANNEL_KEY, serialize([new Envelope()]), FailureCache::CACHE_CHANNEL_TTL_SEC);

        $failureCache = new FailureCache($cacheMock);
        $failureCache->add(new Envelope());
    }

    public function test_adding_to_failure_cache_when_cache_is_not_empty()
    {
        $cacheMock = $this->createMock(CacheInterface::class);

        $cacheMock->method('has')->willReturn(true);
        $cacheMock->method('get')->willReturn(serialize([new Envelope()]));
        $cacheMock->expects($this->once())
            ->method('set')
            ->with(FailureCache::CACHE_CHANNEL_KEY, serialize([new Envelope(), new Envelope()]), FailureCache::CACHE_CHANNEL_TTL_SEC);

        $failureCache = new FailureCache($cacheMock);
        $failureCache->add(new Envelope());
    }
}
