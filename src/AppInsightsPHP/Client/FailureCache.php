<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Channel\Contracts\Envelope;
use Psr\SimpleCache\CacheInterface;

final class FailureCache
{
    public const CACHE_CHANNEL_KEY = 'app_insights_php.failure_cache';
    public const CACHE_CHANNEL_TTL_SEC = 86400;

    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function add(Envelope ...$envelopes): void
    {
        if ($this->cache->has(self::CACHE_CHANNEL_KEY)) {
            $envelopes = \array_merge(
                unserialize($this->cache->get(self::CACHE_CHANNEL_KEY)),
                $envelopes
            );
        }

        $this->cache->set(self::CACHE_CHANNEL_KEY, serialize($envelopes), self::CACHE_CHANNEL_TTL_SEC);
    }

    public function purge(): void
    {
        $this->cache->delete(self::CACHE_CHANNEL_KEY);
    }

    public function all(): array
    {
        if ($this->cache->has(self::CACHE_CHANNEL_KEY)) {
            return unserialize($this->cache->get(self::CACHE_CHANNEL_KEY));
        }

        return [];
    }

    public function empty(): bool
    {
        return \count($this->all()) === 0;
    }
}

