<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client\Configuration;

final class Dependenies
{
    private $enabled;

    private $ignoredDependencies;

    public function __construct(bool $enabled, array $ignoredDependencies = [])
    {
        $this->enabled = $enabled;
        $this->ignoredDependencies = $ignoredDependencies;
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

    public function ignore(string $dependencyName) : void
    {
        $this->ignoredDependencies[] = $dependencyName;
    }

    public function isIgnored(string $dependencyName) : bool
    {
        return \in_array($dependencyName, $this->ignoredDependencies, true);
    }
}
