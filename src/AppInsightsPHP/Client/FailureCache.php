<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client;

use ApplicationInsights\Channel\Contracts\Envelope;

interface FailureCache
{
    public function add(Envelope ...$envelopes): void;

    public function flush(): void;
}
