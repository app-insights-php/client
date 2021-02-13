<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Channel\Contracts\Envelope;
use ApplicationInsights\Channel\Telemetry_Channel;
use ApplicationInsights\Telemetry_Client;

final class SendOne
{
    public const TIME_48H_IN_SECONDS = 172800;

    public function __invoke(Telemetry_Client $telemetryClient, Envelope $envelope) : void
    {
        if ($envelope->getTime() === null) {
            return;
        }

        $nowTimestamp = (new \DateTimeImmutable())->getTimestamp();
        $envelopeTimestamp = (new \DateTimeImmutable($envelope->getTime()))->getTimestamp();

        if (($nowTimestamp - $envelopeTimestamp) > self::TIME_48H_IN_SECONDS) {
            return;
        }

        /**
         * Telemetry_Channel is cloned here because it is not immutable. FailureCache is going to work on the
         * Telemetry_Channel's queue to send every failure in a separate request to avoid sending too big
         * requests. Working on the provided copy of the Telemetry_Channel's object could introduce bugs which
         * would be hard to debug.
         */
        $telemetryChannel = new Telemetry_Channel(
            $telemetryClient->getChannel()->getEndpointUrl(),
            $telemetryClient->getChannel()->GetClient()
        );
        $telemetryChannel->setSendGzipped($telemetryClient->getChannel()->getSendGzipped());
        $telemetryChannel->setQueue([$envelope]);
        $telemetryChannel->send();
    }
}
