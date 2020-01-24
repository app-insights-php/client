<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Channel\Contracts\Request_Data;
use ApplicationInsights\Channel\Telemetry_Channel;
use ApplicationInsights\Telemetry_Client;
use ApplicationInsights\Telemetry_Context;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class Client
{
    private $client;
    private $configuration;
    private $failureCache;
    private $fallbackLogger;

    public function __construct(
        Telemetry_Client $client,
        Configuration $configuration,
        FailureCache $failureCache,
        LoggerInterface $fallbackLogger
    ) {
        $this->client = $client;
        $this->configuration = $configuration;
        $this->failureCache = $failureCache;
        $this->fallbackLogger = $fallbackLogger;

        $this->client->getChannel()->setSendGzipped($this->configuration->gzipEnabled());
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
            $response = $this->client->flush();
        } catch (\Throwable $e) {
            $this->failureCache->add(...$this->client->getChannel()->getQueue());
            $this->fallbackLogger->error(
                sprintf('Exception occurred while flushing App Insights Telemetry Client: %s', $e->getMessage()),
                \json_decode($this->client->getChannel()->getSerializedQueue(), true)
            );

            return $e instanceof RequestException ? $e->getResponse() : null;
        }

        try {
            if ($this->failureCache->empty()) {
                return $response;
            }

            $failures = [];
            foreach ($this->failureCache->all() as $item) {
                try {
                    (new SendOne)($this->client, $item);
                } catch (\Throwable $e) {
                    $this->fallbackLogger->error(
                        sprintf('Exception occurred while flushing App Insights Telemetry Client: %s', $e->getMessage()),
                        [
                            'item' => \json_encode($item),
                            'exception' => $e
                        ]
                    );

                    $failures[] = $item;
                }
            }

            $this->failureCache->purge();

            if (\count($failures) > 0) {
                $this->failureCache->add(...$failures);
            }
        } catch (\Throwable $e) {
            $this->fallbackLogger->error(
                sprintf('Exception occurred while flushing App Insights Failure Cache: %s', $e->getMessage()),
                ['exception' => $e]
            );
        }

        return null;
    }

    public function getContext(): Telemetry_Context
    {
        return $this->client->getContext();
    }

    public function getChannel(): Telemetry_Channel
    {
        return $this->client->getChannel();
    }

    public function trackPageView(string $name, string $url, int $duration = 0, array $properties = NULL, array $measurements = NULL): void
    {
        if (!$this->configuration->isEnabled()) {
            return;
        }

        TelemetryData::pageView($name, $url, $properties, $measurements)->validate();
        $this->client->trackPageView($name, $url, $duration, $properties, $measurements);
    }

    public function trackMetric(string $name, float $value, int $type = NULL, int $count = NULL, float $min = NULL, float $max = NULL, float $stdDev = NULL, array $properties = NULL): void
    {
        if (!$this->configuration->isEnabled()) {
            return;
        }

        TelemetryData::metric($name, $properties)->validate();
        $this->client->trackMetric($name, $value, $type, $count, $min, $max, $stdDev, $properties);
    }

    public function trackEvent(string $name, array $properties = NULL, array $measurements = NULL): void
    {
        if (!$this->configuration->isEnabled()) {
            return;
        }

        TelemetryData::event($name, $properties, $measurements)->validate();
        $this->client->trackEvent($name, $properties, $measurements);
    }

    public function trackMessage(string $message, int $severityLevel = NULL, array $properties = NULL): void
    {
        if (!$this->configuration->isEnabled() || !$this->configuration->traces()->isEnabled()) {
            return;
        }

        TelemetryData::message($message, $properties)->validate();
        $this->client->trackMessage($message, $severityLevel, $properties);
    }

    public function trackRequest(string $name, string $url, int $startTime, int $durationInMilliseconds = 0, int $httpResponseCode = 200, bool $isSuccessful = true, array $properties = NULL, array $measurements = NULL): void
    {
        if (!$this->configuration->isEnabled() || !$this->configuration->requests()->isEnabled()) {
            return;
        }

        TelemetryData::request($name, $url, $properties, $measurements)->validate();
        $this->client->trackRequest($name, $url, $startTime, $durationInMilliseconds, $httpResponseCode, $isSuccessful, $properties, $measurements);
    }

    public function beginRequest(string $name, string $url, int $startTime): ?Request_Data
    {
        if (!$this->configuration->isEnabled() || !$this->configuration->requests()->isEnabled()) {
            return null;
        }

        return $this->client->beginRequest($name, $url, $startTime);
    }

    public function endRequest(?Request_Data $request, int $durationInMilliseconds = 0, int $httpResponseCode = 200, bool $isSuccessful = true, array $properties = NULL, array $measurements = NULL): void
    {
        if (!$this->configuration->isEnabled() || !$this->configuration->requests()->isEnabled()) {
            return;
        }

        TelemetryData::request((string) $request->getName(), (string) $request->getUrl(), $properties, $measurements)->validate();
        $this->client->endRequest($request, $durationInMilliseconds, $httpResponseCode, $isSuccessful, $properties, $measurements);
    }

    public function trackException(\Throwable $exception, array $properties = NULL, array $measurements = NULL): void
    {
        if (!$this->configuration->isEnabled() ||
            !$this->configuration->exceptions()->isEnabled() ||
            $this->configuration->exceptions()->isIgnored(\get_class($exception))
        ) {
            return;
        }

        TelemetryData::exception($exception, $properties, $measurements)->validate();
        $this->client->trackException($exception, $properties, $measurements);
    }

    public function trackDependency(string $name, string $type = "", string $commandName = NULL, int $startTime = NULL, int $durationInMilliseconds = 0, bool $isSuccessful = true, int $resultCode = NULL, array $properties = NULL): void
    {
        if (!$this->configuration->isEnabled() ||
            !$this->configuration->dependencies()->isEnabled() ||
            $this->configuration->dependencies()->isIgnored($name)
        ) {
            return;
        }

        TelemetryData::dependency($name, $type, $commandName, $properties)->validate();
        $this->client->trackDependency($name, $type, $commandName, $startTime, $durationInMilliseconds, $isSuccessful, $resultCode, $properties);
    }
}
