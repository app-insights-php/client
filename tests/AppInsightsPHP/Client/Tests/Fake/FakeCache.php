<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client\Tests\Fake;

use Psr\SimpleCache\CacheInterface;

final class FakeCache implements CacheInterface
{
    private $cache = [];

    public function get($key, $default = null): string
    {
        return $this->cache[$key];
    }

    public function set($key, $value, $ttl = null): void
    {
        $this->cache[$key] = $value;
    }

    public function delete($key): void
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        }
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }

    public function getMultiple($keys, $default = null): void
    {
        // TODO: Implement getMultiple() method.
    }

    public function setMultiple($values, $ttl = null): void
    {
        // TODO: Implement setMultiple() method.
    }

    public function deleteMultiple($keys): void
    {
        // TODO: Implement deleteMultiple() method.
    }

    public function has($key): bool
    {
        if (isset($this->cache[$key])) {
            return true;
        }

        return false;
    }
}
