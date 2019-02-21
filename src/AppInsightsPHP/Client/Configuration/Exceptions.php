<?php

declare (strict_types=1);

namespace AppInsightsPHP\Client\Configuration;

final class Exceptions
{
    private $ignoredExceptions;
    private $enabled;

    public function __construct(bool $enabled, array $ignoredExceptions = [])
    {
        foreach ($ignoredExceptions as $exceptionClass) {
            if (!\class_exists($exceptionClass)) {
                throw new \RuntimeException(sprintf('Exception class "%s" ignored by app_insights_php ignored_exceptions options does not exists', $exceptionClass));
            }
        }

        $this->ignoredExceptions = $ignoredExceptions;
        $this->enabled = $enabled;
    }

    public function isIgnored(string $exceptionClass) : bool
    {
        return (bool) array_filter(
            $this->ignoredExceptions,
            function(string $ignoredExceptionClass) use ($exceptionClass) {
                return $ignoredExceptionClass === $exceptionClass;
            }
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

    public function ignore(string $exceptionClass) : void
    {
        if (!\class_exists($exceptionClass)) {
            throw new \RuntimeException(sprintf('Exception class "%s" ignored by app_insights_php ignored_exceptions options does not exists', $exceptionClass));
        }

        if (\in_array($exceptionClass, $this->ignoredExceptions)) {
            return ;
        }

        $this->ignoredExceptions[] = $exceptionClass;
    }
}