<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Telemetry_Client;
use Psr\Log\LoggerInterface;

final class ClientFactory implements ClientFactoryInterface
{
    private $instrumentationKey;
    private $configuration;
    private $failureCache;
    private $fallbackLogger;

    public function __construct(
        string $instrumentationKey,
        Configuration $configuration,
        CacheInterface $failureCache,
        LoggerInterface $fallbackLogger
    ) {
        $this->instrumentationKey = $instrumentationKey;
        $this->configuration = $configuration;
        $this->failureCache = $failureCache;
        $this->fallbackLogger = $fallbackLogger;
    }

    public function create() : Client
    {
        $client = new Telemetry_Client();
        $client->getContext()->setInstrumentationKey($this->instrumentationKey);

        return new Client(
            $client,
            $this->configuration,
            new FailureCache($this->failureCache),
            $this->fallbackLogger
        );
    }
}
