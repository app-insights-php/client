<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client;

final class ResponseMessage
{
    private $message;

    private function __construct(string $message)
    {
        $this->message = $message;
    }

    public static function message(string $message): self
    {
        return new self($message);
    }

    public function isToOldTimeInEnvelope(): bool
    {
        return false !== \strpos($this->message, "Field 'time' on type 'Envelope' is older than the allowed min date.");
    }
}