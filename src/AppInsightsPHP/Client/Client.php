<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Telemetry_Client;

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
 * @method void flush($options = array(), $sendAsync = false)
 */
final class Client
{
    private $client;
    private $configuration;

    public function __construct(Telemetry_Client $client, Configuration $configuration)
    {
        $this->client = $client;
        $this->configuration = $configuration;
    }

    public function configuration(): Configuration
    {
        return $this->configuration;
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

        if (\in_array($name, ['trackDependency']) && !$this->configuration->dependencies()->isEnabled()) {
            return ;
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