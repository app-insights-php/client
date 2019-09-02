<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client\FailureCache;

use AppInsightsPHP\Client\FailureCache;
use ApplicationInsights\Channel\Contracts\Envelope;

final class DisabledFailureCache implements FailureCache
{
    public function add(Envelope ...$envelopes): void
    {
        // do nothing, it's disabled
    }

    public function flush(): void
    {
        // do nothing, it's disabled
    }
}
