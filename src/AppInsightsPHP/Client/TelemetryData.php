<?php

declare(strict_types=1);

namespace AppInsightsPHP\Client;

final class TelemetryData
{
    private $data;

    private function __construct(...$data)
    {
        $this->data = $data;
    }

    public static function pageView(string $name, string $url, array $properties = NULL, array $measurements = NULL): self
    {
        return new self($name, $url, $properties, $measurements);
    }

    public static function metric(string $name, array $properties = NULL): self
    {
        return new self($name, $properties);
    }

    public static function event(string $name, array $properties = NULL, array $measurements = NULL): self
    {
        return new self($name, $properties, $measurements);
    }

    public static function message(string $message, array $properties = NULL): self
    {
        return new self($message, $properties);
    }

    public static function request(string $name, string $url, array $properties = NULL, array $measurements = NULL): self
    {
        return new self($name, $url, $properties, $measurements);
    }

    public static function exception(\Throwable $exception, array $properties = NULL, array $measurements = NULL): self
    {
        return new self($exception->getTraceAsString(), $properties, $measurements);
    }

    public static function dependency(string $name, string $type, string $commandName = NULL, array $properties = NULL): self
    {
        return new self($name, $type, $commandName, $properties);
    }

    public function exceededMaximumSize(): bool
    {
        return \strlen((string) \json_encode($this->data)) > 65000;
    }

    public function validate(): void
    {
        if ($this->exceededMaximumSize()) {
            throw new \RuntimeException('Telemetry exceeded the maximum size of 65kb: '.\json_encode($this->data));
        };
    }
}
