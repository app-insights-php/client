<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client\FailureCache;

use AppInsightsPHP\Client\FailureCache;
use ApplicationInsights\Channel\Contracts\Envelope;
use ApplicationInsights\Channel\Telemetry_Channel;
use ApplicationInsights\Telemetry_Client;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

final class PsrFailureCache implements FailureCache
{
    private const CACHE_CHANNEL_KEY = 'app_insights_php.failure_cache';
    private const CACHE_CHANNEL_TTL_SEC = 86400;

    private $failureCache;
    private $telemetryChannel;
    private $fallbackLogger;

    public function __construct(
        CacheInterface $failureCache,
        Telemetry_Client $telemetryClient,
        LoggerInterface $fallbackLogger = null
    ) {
        $this->failureCache = $failureCache;
        $this->fallbackLogger = $fallbackLogger;

        /**
         * Telemetry_Channel is cloned here because it is not immutable. FailureCache is going to work on the
         * Telemetry_Channel's queue to send every failure in a separate request to avoid sending too big
         * requests. Working on the provided copy of the Telemetry_Channel's object could introduce bugs which
         * would be hard to debug.
         */
        $this->telemetryChannel = new Telemetry_Channel(
            $telemetryClient->getChannel()->getEndpointUrl(),
            $telemetryClient->getChannel()->GetClient()
        );
        $this->telemetryChannel->setSendGzipped($telemetryClient->getChannel()->getSendGzipped());
    }

    public function add(Envelope ...$envelopes): void
    {
        if ($this->failureCache->has(self::CACHE_CHANNEL_KEY)) {
            $envelopes = \array_merge(
                unserialize($this->failureCache->get(self::CACHE_CHANNEL_KEY)),
                $envelopes
            );
        }

        $this->failureCache->set(self::CACHE_CHANNEL_KEY, serialize($envelopes), self::CACHE_CHANNEL_TTL_SEC);
    }

    public function flush(): void
    {
        if (!$this->failureCache->has(self::CACHE_CHANNEL_KEY)) {
            return;
        }

        /** @var Envelope[] $envelopes */
        $envelopes = unserialize($this->failureCache->get(self::CACHE_CHANNEL_KEY));
        $this->failureCache->delete(self::CACHE_CHANNEL_KEY);
        $failures = [];

        foreach ($envelopes as $envelope) {
            try {
                $this->telemetryChannel->setQueue([$envelope]);
                $this->telemetryChannel->send();
            } catch (\Throwable $e) {
                if ($this->fallbackLogger) {
                    $this->fallbackLogger->error(
                        sprintf('Exception occurred while flushing App Insights Telemetry Client: %s', $e->getMessage()),
                        [
                            'envelope' => $envelope->jsonSerialize(),
                            'exception' => $e
                        ]
                    );
                }

                $failures[] = $envelope;
            }
        }

        if (\count($failures) > 0) {
            $this->add(...$failures);
        }
    }
}
