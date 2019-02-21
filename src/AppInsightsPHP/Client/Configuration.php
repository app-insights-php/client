<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

use AppInsightsPHP\Client\Configuration\Dependenies;
use AppInsightsPHP\Client\Configuration\Exceptions;
use AppInsightsPHP\Client\Configuration\Requests;
use AppInsightsPHP\Client\Configuration\Traces;

final class Configuration
{
    private $enabled;
    private $exceptions;
    private $dependencies;
    private $requests;
    private $traces;

    public function __construct(
        bool $enabled,
        Exceptions $exceptions,
        Dependenies $dependenies,
        Requests $requests,
        Traces $traces
    ) {
        $this->enabled = $enabled;
        $this->exceptions = $exceptions;
        $this->dependencies = $dependenies;
        $this->requests = $requests;
        $this->traces = $traces;
    }

    public static function createDefault()
    {
        return new self(
            $enabled = true,
            new Configuration\Exceptions($enabled = true),
            new Configuration\Dependenies($enabled = true),
            new Configuration\Requests($enabled = true),
            new Configuration\Traces($enabled = true)
        );
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

    public function exceptions(): Exceptions
    {
        return $this->exceptions;
    }

    public function dependencies(): Dependenies
    {
        return $this->dependencies;
    }

    public function requests(): Requests
    {
        return $this->requests;
    }

    public function traces(): Traces
    {
        return $this->traces;
    }
}