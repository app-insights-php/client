<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Telemetry_Client;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @method \ApplicationInsights\Telemetry_Context getContext()
 * @method \ApplicationInsights\Channel\Telemetry_Channel getChannel()
 *
 * @method void trackPageView($name, $url, $duration = 0, $properties = NULL, $measurements = NULL)
 * @method void trackMetric($name, $value, $type = NULL, $count = NULL, $min = NULL, $max = NULL, $stdDev = NULL, $properties = NULL)
 * @method void trackEvent($name, $properties = NULL, $measurements = NULL)
 * @method void trackMessage($message, $severityLevel = NULL, $properties = NULL)
 * @method void trackRequest($name, $url, $startTime, $durationInMilliseconds = 0, $httpResponseCode = 200, $isSuccessful = true, $properties = NULL, $measurements = NULL)
 * @method \ApplicationInsights\Channel\Contracts\Request_Data beginRequest($name, $url, $startTime )
 * @method void endRequest(\ApplicationInsights\Channel\Contracts\Request_Data $request, $durationInMilliseconds = 0, $httpResponseCode = 200, $isSuccessful = true, $properties = NULL, $measurements = NULL)
 * @method void trackException($ex, $properties = NULL, $measurements = NULL)
 * @method void trackDependency($name,$type = "",$commandName = NULL,$startTime = NULL,$durationInMilliseconds = 0,$isSuccessful = true,$resultCode = NULL,$properties = NULL)
 */
final class Client
{
    public const CACHE_CHANNEL_KEY = 'app_insights_php.failure_cache';
    public const CACHE_CHANNEL_TTL_SEC = 86400; // 1 day

    private $client;
    private $configuration;
    private $failureCache;

    public function __construct(
        Telemetry_Client $client,
        Configuration $configuration,
        CacheInterface $failureCache = null
    ) {
        $this->client = $client;
        $this->configuration = $configuration;
        $this->failureCache = $failureCache;
    }

    public function configuration(): Configuration
    {
        return $this->configuration;
    }

    public function flush(): ?ResponseInterface
    {
        if (!$this->configuration->isEnabled()) {
            return null;
        }

        try {
            if ($this->failureCache && $this->failureCache->has(self::CACHE_CHANNEL_KEY)) {
                $this->client->getChannel()->setQueue(
                    array_merge(
                        unserialize($this->failureCache->get(self::CACHE_CHANNEL_KEY)),
                        $this->client->getChannel()->getQueue()
                    )
                );

                $this->failureCache->delete(self::CACHE_CHANNEL_KEY);
            }

            return $this->client->flush();
        } catch (\Throwable $e) {
            if ($this->failureCache) {
                $queueContent = $this->client->getChannel()->getQueue();

                if ($this->failureCache->has(self::CACHE_CHANNEL_KEY)) {
                    $previousQueueContent = unserialize($this->failureCache->get(self::CACHE_CHANNEL_KEY));
                    $queueContent = array_merge($previousQueueContent, $queueContent);
                }

                $this->failureCache->set(self::CACHE_CHANNEL_KEY, serialize($queueContent), self::CACHE_CHANNEL_TTL_SEC);
            }

            throw $e;
        }
    }

    public function __call($name, $arguments)
    {
        if (\in_array($name, ['getContext', 'getChannel'])) {
            return $this->client->$name();
        }

        if (!$this->configuration->isEnabled()) {
            return ;
        }

        if (\in_array($name, ['beginRequest', 'endRequest', 'trackRequest']) && !$this->configuration->requests()->isEnabled()) {
            return ;
        }

        if (\in_array($name, ['trackDependency'])) {
            if (!$this->configuration->dependencies()->isEnabled() || $this->configuration->dependencies()->isIgnored($arguments[0])) {
                return;
            }
        }

        if (\in_array($name, ['trackException'])) {
            if (!$this->configuration->exceptions()->isEnabled()) {
                return;
            }

            if ($this->configuration->exceptions()->isIgnored(\get_class($arguments[0]))) {
                return ;
            }
        }

        if (\in_array($name, ['trackMessage']) && !$this->configuration->traces()->isEnabled()) {
            return ;
        }

        return $this->client->$name(...$arguments);
    }
}