<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Telemetry_Client;
use Psr\SimpleCache\CacheInterface;

final class ClientFactory implements ClientFactoryInterface
{
    private $instrumentationKey;
    private $configuration;
    private $failureCache;

    public function __construct(
        string $instrumentationKey,
        Configuration $configuration,
        CacheInterface $failureCache = null
    ) {
        $this->instrumentationKey = $instrumentationKey;
        $this->configuration = $configuration;
        $this->failureCache = $failureCache;
    }

    public function create() : Client
    {
        $client = new Telemetry_Client();
        $client->getContext()->setInstrumentationKey($this->instrumentationKey);

        return new Client(
            $client,
            $this->configuration,
            $this->failureCache
        );
    }
}