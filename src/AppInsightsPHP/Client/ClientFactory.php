<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Telemetry_Client;

final class ClientFactory implements ClientFactoryInterface
{
    private $instrumentationKey;
    private $configuration;

    public function __construct(string $instrumentationKey, Configuration $configuration)
    {
        $this->instrumentationKey = $instrumentationKey;
        $this->configuration = $configuration;
    }

    public function create() : Client
    {
        $client = new Telemetry_Client();
        $client->getContext()->setInstrumentationKey($this->instrumentationKey);

        return new Client($client, $this->configuration);
    }
}