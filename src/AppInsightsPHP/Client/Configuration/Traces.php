<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client\Configuration;

final class Traces
{
    private $enabled;

    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function disable() : void
    {
        $this->enabled = false;
    }

    public function enable() : void
    {
        $this->enabled = true;
    }

    public function isEnabled() : bool
    {
        return $this->enabled;
    }
}
